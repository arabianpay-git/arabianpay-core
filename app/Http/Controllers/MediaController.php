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
        if (in_array(Auth::user()->user_type, ['admin', 'employee'])) {
            $media = Media::with('user')
                ->latest()
                ->take(18)
                ->get();
        } else {
            $media = Media::with('user')->where('user_id', Auth::user()->id)
                ->latest()
                ->take(18)
                ->get();
        }

        return view('media.index', compact('media'));
    }

    public function lazyLoad(Request $request)
    {
        $offset = (int) $request->input('offset', 0);
        $limit = 18;

        if (in_array(Auth::user()->user_type, ['admin', 'employee'])) {
            $query = Media::with('user')->latest();
        } else {
            $query = Media::with('user')->where('user_id', Auth::user()->id)
                ->latest();
        }

        $items = $query->skip($offset)->take($limit)->get();

        $media = $items->map(function ($m) {
            return [
                'id' => $m->id,
                'name' => $m->name,
                'file_name' => $m->file_name,
                'size' => $m->size,
                'mime_type' => $m->mime_type,
                'url' => supplierMedia($m->file_name),
                // Include user first_name and last_name for JS use
                'user' => $m->user ? [
                    'first_name' => $m->user->first_name,
                    'last_name' => $m->user->last_name,
                ] : null,
            ];
        })->toArray();

        return response()->json(['media' => $media]);
    }

    public function refresh(Request $request)
    {
        $limit = $request->get('limit', 18);

        if (in_array(Auth::user()->user_type, ['admin', 'employee'])) {
            $mediaQuery = Media::with('user')

                ->latest();
        } else {
            $mediaQuery = Media::with('user')
                ->where('user_id', Auth::id())
                ->latest();
        }

        $media = $mediaQuery->take($limit)->get();
        $html  = view('media._grid', compact('media'))->render();

        return response()->json(['html' => $html]);
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
                    Storage::disk($disk)->putFileAs($folder, $file, $filename);
                }
            } elseif (in_array($extension, ['mp4', 'mov', 'avi', 'mkv'])) {
                // Video handling
                try {
                    $ffmpeg = \FFMpeg\FFMpeg::create();
                    $video = $ffmpeg->open($file->getPathname());
                    $ffprobe = \FFMpeg\FFProbe::create();
                    $duration = $ffprobe
                        ->format($file->getPathname())
                        ->get('duration');

                    if ($duration > 32) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Video "' . $originalName . '" is longer than 30 seconds.',
                        ], 422);
                    }
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to read video "' . $originalName . '".',
                    ], 422);
                }

                // Store video
                Storage::disk($disk)->putFileAs($folder, $file, $filename);

                // Generate thumbnail
                try {
                    $ffmpeg = \FFMpeg\FFMpeg::create();
                    $video = $ffmpeg->open($file->getPathname());
                    $frameName = Str::random(40) . '.jpg';
                    $thumbnailPath = storage_path("app/public/{$folder}/$frameName");

                    $video->frame(\FFMpeg\Coordinate\TimeCode::fromSeconds(1))
                        ->save($thumbnailPath);
                } catch (\Exception $e) {
                    Log::error("FFMpeg failed to generate thumbnail: " . $e->getMessage());
                }
            } else {
                // ✅ FIX: PDFs & others stored consistently
                Storage::disk($disk)->putFileAs($folder, $file, $filename);
            }

            // Store DB record
            $media = Media::create([
                'user_id'   => Auth::id(),
                'name'      => $originalName,
                'file_name' => $filename,
                'mime_type' => $file->getMimeType(),
                'size'      => Storage::disk($disk)->size($fullPath), // now always exists
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
