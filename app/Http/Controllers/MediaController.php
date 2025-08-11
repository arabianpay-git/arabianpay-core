<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    public function index()
    {
        if (Auth::user()->user_type === 'admin') {
            $media = Media::latest()->take(18)->get();
        } else {
            $media = Media::where('user_id', Auth::user()->id)->latest()->take(18)->get();
        }

        return view('media.index', compact('media'));
    }

    public function lazyLoad(Request $request)
    {
        $offset = $request->input('offset', 0);
        $limit = 18;

        if (Auth::user()->user_type === 'admin') {
            $media = Media::latest()
                ->skip($offset)
                ->take($limit)
                ->get();
        } else {
            $media = Media::where('user_id', Auth::user()->id)
                ->latest()
                ->skip($offset)
                ->take($limit)
                ->get();
        }

        return response()->json([
            'media' => $media,
        ]);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'files'   => 'required|array',
            'files.*' => [
                'file',
                'mimes:jpeg,png,jpg,webp,gif,svg,pdf,mp4,mov,avi,mkv',
                'max:5120', // 5MB max
            ],
        ], [
            'files.required'   => 'Please select at least one file to upload.',
            'files.*.file'     => 'Each item must be a valid file.',
            'files.*.mimes'    => 'Only JPEG, PNG, JPG, WEBP, GIF, SVG, PDF, and video files (MP4, MOV, AVI, MKV) are allowed.',
            'files.*.max'      => 'Video files must not be larger than 5MB.',
        ]);


        $uploadedMedia = [];
        $disk = 'public';
        $folder = 'media';

        foreach ($request->file('files') as $file) {
            $originalName = $file->getClientOriginalName();
            $extension    = strtolower($file->getClientOriginalExtension());
            $filename     = Str::random(40) . '.' . $extension;
            $fullPath     = "$folder/$filename";

            if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                // Compress images
                if (in_array($extension, ['jpg', 'jpeg'])) {
                    $resource = imagecreatefromjpeg($file->getPathname());
                } else {
                    $resource = imagecreatefrompng($file->getPathname());
                    imagealphablending($resource, false);
                    imagesavealpha($resource, true);
                }

                if ($resource) {
                    $quality = in_array($extension, ['jpg', 'jpeg']) ? 75 : 6;
                    $maxBytes = 1024 * 1024;
                    $compressedData = null;

                    do {
                        ob_start();
                        if (in_array($extension, ['jpg', 'jpeg'])) {
                            imagejpeg($resource, null, $quality);
                        } else {
                            imagepng($resource, null, $quality);
                        }
                        $compressedData = ob_get_clean();

                        if (strlen($compressedData) > $maxBytes) {
                            if (in_array($extension, ['jpg', 'jpeg'])) {
                                $quality = max($quality - 5, 10);
                            } else {
                                $quality = min($quality + 1, 9);
                            }
                        }
                    } while (
                        strlen($compressedData) > $maxBytes
                        && (($extension !== 'png' && $quality > 10)
                            || ($extension === 'png' && $quality < 9))
                    );

                    imagedestroy($resource);
                    Storage::disk($disk)->put($fullPath, $compressedData);
                } else {
                    $file->storeAs($folder, $filename, $disk);
                }
            } elseif (in_array($extension, ['mp4', 'mov', 'avi', 'mkv'])) {

                if (in_array($extension, ['mp4', 'mov', 'avi', 'mkv'])) {
                    // Check video duration
                    try {
                        $ffmpeg = \FFMpeg\FFMpeg::create();
                        $video = $ffmpeg->open($file->getPathname());
                        $ffprobe = \FFMpeg\FFProbe::create();
                        $duration = $ffprobe
                            ->format($file->getPathname()) // path to video file
                            ->get('duration');

                        if ($duration > 32) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Video "' . $originalName . '" is longer than 30 seconds.',
                            ], 422);
                        }

                        // proceed with storage...
                    } catch (\Exception $e) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Failed to read video "' . $originalName . '".',
                        ], 422);
                    }
                }

                // Store video
                $file->storeAs($folder, $filename, $disk);

                // Generate thumbnail using FFMpeg
                try {
                    $ffmpeg = \FFMpeg\FFMpeg::create();
                    $video = $ffmpeg->open($file->getPathname());
                    $frameName = Str::random(40) . '.jpg';
                    $thumbnailPath = storage_path("app/public/{$folder}/$frameName");

                    $video->frame(\FFMpeg\Coordinate\TimeCode::fromSeconds(1))
                        ->save($thumbnailPath);

                    // Optionally: save thumbnail info (not required in your current DB)
                } catch (\Exception $e) {
                    Log::error("FFMpeg failed to generate thumbnail: " . $e->getMessage());
                }
            } else {
                // Other types (pdf, svg, etc)
                $file->storeAs($folder, $filename, $disk);
            }

            // Store DB record
            $media = Media::create([
                'user_id'   => Auth::id(),
                'name'      => $originalName,
                'file_name' => $filename,
                'mime_type' => $file->getMimeType(),
                'size'      => Storage::disk($disk)->size($fullPath),
                'disk'      => $disk,
                'folder'    => $folder,
            ]);

            $media->logModelAction(
                event: 'upload',
                description: Auth::user()->first_name . " " . Auth::user()->last_name . " uploaded a file: {$originalName} [{$media->id}]",
                properties: [
                    'ip' => request()->ip(),
                    'batch_uuid' => (string) Str::uuid(),
                ]
            );

            $uploadedMedia[] = $media;
        }

        return response()->json([
            'success' => true,
            'message' => 'Files uploaded successfully!',
            'media'   => $uploadedMedia,
        ]);
    }


    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:media,id'
        ]);

        $ids = $request->input('ids');
        $mediaItems = Media::whereIn('id', $ids)->get();

        foreach ($mediaItems as $media) {
            // Log delete action
            $media->logModelAction(
                event: 'delete',
                description: Auth::user()->first_name . " " . Auth::user()->last_name . " deleted a file: {$media->name} [{$media->id}]",
                properties: [
                    'ip' => request()->ip(),
                    'batch_uuid' => (string) Str::uuid(),
                ]
            );

            // Delete main file
            $filePath = $media->folder . '/' . $media->file_name;
            if (Storage::disk($media->disk)->exists($filePath)) {
                Storage::disk($media->disk)->delete($filePath);
            }

            // 🔽 Optional: Delete video thumbnail (if stored)
            // Assuming thumbnails are stored as "thumb_{$file_name}.jpg"
            $possibleThumb = $media->folder . '/thumb_' . pathinfo($media->file_name, PATHINFO_FILENAME) . '.jpg';
            if (Storage::disk($media->disk)->exists($possibleThumb)) {
                Storage::disk($media->disk)->delete($possibleThumb);
            }

            // Delete DB record
            $media->delete();
        }

        return response()->json([
            'message' => 'Selected media files have been deleted successfully.'
        ]);
    }
}
