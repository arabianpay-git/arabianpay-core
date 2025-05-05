<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    public function index()
    {
        $media = Media::where('user_id', Auth::user()->id)->latest()->take(18)->get();
        return view('media.index', compact('media'));
    }

    public function lazyLoad(Request $request)
    {
        $offset = $request->input('offset', 0);
        $limit = 18;

        $media = Media::where('user_id', Auth::user()->id)->latest()->skip($offset)->take($limit)->get();

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
                'mimes:jpeg,png,jpg,gif,svg,pdf',
                // Removed max-size rule because we auto-compress larger files  
                // 'max:1048'
            ],
        ], [
            'files.required'   => 'Please select at least one file to upload.',
            'files.*.file'     => 'Each item must be a valid file.',
            'files.*.mimes'    => 'Only JPEG, PNG, JPG, GIF, SVG, and PDF files are allowed.',
            // Removed files.*.max message  
        ]);


        $uploadedMedia = [];

        foreach ($request->file('files') as $file) {
            $originalName = $file->getClientOriginalName();
            $extension    = strtolower($file->getClientOriginalExtension());
            $filename     = Str::random(40) . '.' . $extension;                   // Changed
            $disk         = 'public';
            $folder       = 'media';
            $fullPath     = "$folder/$filename";

            // Only attempt compression for JPEG/PNG
            if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                // Create image resource
                if (in_array($extension, ['jpg', 'jpeg'])) {
                    $resource = imagecreatefromjpeg($file->getPathname());
                } else { // png
                    $resource = imagecreatefrompng($file->getPathname());
                    imagealphablending($resource, false);
                    imagesavealpha($resource, true);
                }

                if ($resource) {
                    // Initial quality/compression
                    $quality        = in_array($extension, ['jpg', 'jpeg']) ? 75 : 6; // Changed
                    $maxBytes       = 1024 * 1024;                                   // 1 MB
                    $compressedData = null;

                    // Loop: compress and check size until under 1MB or quality floor reached
                    do {
                        ob_start();
                        if (in_array($extension, ['jpg', 'jpeg'])) {
                            imagejpeg($resource, null, $quality);
                        } else {
                            imagepng($resource, null, $quality);
                        }
                        $compressedData = ob_get_clean();

                        // If still too big, reduce quality
                        if (strlen($compressedData) > $maxBytes) {
                            if (in_array($extension, ['jpg', 'jpeg'])) {
                                $quality = max($quality - 5, 10);            // Changed: floor at 10
                            } else {
                                $quality = min($quality + 1, 9);             // Changed: max PNG level 9
                            }
                        }
                    } while (
                        strlen($compressedData) > $maxBytes
                        && (($extension !== 'png' && $quality > 10)
                            || ($extension === 'png' && $quality < 9))
                    );

                    imagedestroy($resource);

                    // Store the (possibly re-compressed) data
                    Storage::disk($disk)->put($fullPath, $compressedData);
                } else {
                    // fallback if GD fails
                    $file->storeAs($folder, $filename, $disk);
                }
            } else {
                // Non-image: store as-is
                $file->storeAs($folder, $filename, $disk);
            }

            // Create DB record with actual size
            $media = Media::create([
                'user_id'   => Auth::id(),
                'name'      => $originalName,
                'file_name' => $filename,
                'mime_type' => $file->getMimeType(),
                'size'      => Storage::disk($disk)->size($fullPath),        // Changed: accurate size
                'disk'      => $disk,
                'folder'    => $folder,
            ]);

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
            Storage::disk('public')->delete('media/' . $media->file_name);

            $media->delete();
        }

        return response()->json([
            'message' => 'Selected media files have been deleted successfully.'
        ]);
    }
}
