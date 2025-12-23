<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Services\AuditTrailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    protected $auditTrailService;

    public function __construct(AuditTrailService $auditTrailService)
    {
        $this->auditTrailService = $auditTrailService;
    }

    public function index()
    {
        // Log view operation for media library
        $this->auditTrailService->logViewOperation(
            'view_library',
            'Media',
            'Viewed media library',
            [
                'user_type' => Auth::user()->user_type,
            ]
        );

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

        // Log lazy load operation
        $this->auditTrailService->log([
            'event_category' => 'view_operations',
            'event_type' => 'lazy_load_media',
            'entity_type' => 'Media',
            'action_summary' => 'Lazy loaded more media files',
            'properties' => [
                'offset' => $offset,
                'limit' => $limit,
                'user_type' => Auth::user()->user_type,
            ],
        ]);

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
                'url' => getMediaUrl($m->file_name),
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

        // Log refresh operation
        $this->auditTrailService->log([
            'event_category' => 'view_operations',
            'event_type' => 'refresh_media',
            'entity_type' => 'Media',
            'action_summary' => 'Refreshed media library',
            'properties' => [
                'limit' => $limit,
                'user_type' => Auth::user()->user_type,
            ],
        ]);

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

        DB::beginTransaction();

        try {
            $uploadedMedia = [];
            $disk = 'public';
            $folder = 'media';
            $batchUuid = (string) Str::uuid();

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
                            // Log video validation failure
                            $this->auditTrailService->log([
                                'event_category' => 'validation_errors',
                                'event_type' => 'video_duration_exceeded',
                                'entity_type' => 'Media',
                                'action_summary' => 'Video duration exceeded 30 seconds limit',
                                'properties' => [
                                    'file_name' => $originalName,
                                    'duration' => $duration,
                                ],
                            ]);

                            DB::rollBack();
                            return response()->json([
                                'success' => false,
                                'message' => 'Video "' . $originalName . '" is longer than 30 seconds.',
                            ], 422);
                        }
                    } catch (\Exception $e) {
                        // Log video processing error
                        $this->auditTrailService->log([
                            'event_category' => 'error_events',
                            'event_type' => 'video_processing_failed',
                            'entity_type' => 'Media',
                            'action_summary' => 'Failed to process video file',
                            'properties' => [
                                'file_name' => $originalName,
                                'error_message' => $e->getMessage(),
                            ],
                        ]);

                        DB::rollBack();
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
                    // Store other file types
                    Storage::disk($disk)->putFileAs($folder, $file, $filename);
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

                // Log media creation with justification
                $justificationData = $this->auditTrailService->withJustification(
                    'File uploaded for business use',
                    'business_operation',
                    ['name'] // File name may contain PII
                );

                $this->auditTrailService->logCreated(
                    $media,
                    "Uploaded file: {$originalName}",
                    array_merge([
                        'file_size' => $media->size,
                        'mime_type' => $media->mime_type,
                        'batch_uuid' => $batchUuid,
                        'is_image' => in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg']),
                        'is_video' => in_array($extension, ['mp4', 'mov', 'avi', 'mkv']),
                        'is_document' => $extension === 'pdf',
                    ], $justificationData)
                );

                $uploadedMedia[] = $media;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Files uploaded successfully!',
                'media'   => $uploadedMedia,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            // Log failed upload attempt
            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'media_upload_failed',
                'entity_type' => 'Media',
                'action_summary' => 'Failed to upload media files',
                'properties' => [
                    'error_message' => $e->getMessage(),
                    'file_count' => count($request->file('files') ?? []),
                ],
            ]);

            Log::error('Media upload error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:media,id'
        ]);

        $ids = $request->input('ids');
        $mediaItems = Media::whereIn('id', $ids)->get();

        if ($mediaItems->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No media files found to delete.'
            ], 404);
        }

        DB::beginTransaction();

        try {
            $batchUuid = (string) Str::uuid();
            $deletedCount = 0;
            $failedDeletions = [];

            foreach ($mediaItems as $media) {
                // Log media deletion with justification
                $justificationData = $this->auditTrailService->withJustification(
                    'File removed due to cleanup or policy compliance',
                    'data_cleanup',
                    ['name'] // File name may contain PII
                );

                $this->auditTrailService->logDeleted(
                    $media,
                    "Deleted file: {$media->name}",
                    array_merge([
                        'file_size' => $media->size,
                        'mime_type' => $media->mime_type,
                        'batch_uuid' => $batchUuid,
                        'user_id' => $media->user_id,
                        'storage_path' => $media->folder . '/' . $media->file_name,
                    ], $justificationData)
                );

                // Delete main file
                $filePath = $media->folder . '/' . $media->file_name;
                $fileDeleted = false;

                if (Storage::disk($media->disk)->exists($filePath)) {
                    Storage::disk($media->disk)->delete($filePath);
                    $fileDeleted = true;
                }

                // Delete video thumbnail if exists
                $thumbnailDeleted = false;
                $possibleThumb = $media->folder . '/thumb_' . pathinfo($media->file_name, PATHINFO_FILENAME) . '.jpg';
                if (Storage::disk($media->disk)->exists($possibleThumb)) {
                    Storage::disk($media->disk)->delete($possibleThumb);
                    $thumbnailDeleted = true;
                }

                // Delete DB record
                $dbDeleted = $media->delete();

                if ($fileDeleted && $dbDeleted) {
                    $deletedCount++;
                } else {
                    $failedDeletions[] = [
                        'id' => $media->id,
                        'name' => $media->name,
                        'file_deleted' => $fileDeleted,
                        'thumbnail_deleted' => $thumbnailDeleted,
                        'db_deleted' => $dbDeleted,
                    ];
                }
            }

            DB::commit();

            // Log bulk deletion summary
            $this->auditTrailService->log([
                'event_category' => 'bulk_operations',
                'event_type' => 'bulk_delete_summary',
                'entity_type' => 'Media',
                'action_summary' => 'Completed bulk deletion of media files',
                'properties' => [
                    'total_requested' => count($ids),
                    'successfully_deleted' => $deletedCount,
                    'failed_deletions' => count($failedDeletions),
                    'failed_details' => $failedDeletions,
                    'batch_uuid' => $batchUuid,
                ],
            ]);

            if (!empty($failedDeletions)) {
                Log::warning('Some media files failed to delete completely', [
                    'failed_deletions' => $failedDeletions
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => "{$deletedCount} media file(s) have been deleted successfully." .
                    (empty($failedDeletions) ? '' : ' Some files may not have been completely removed.'),
                'deleted_count' => $deletedCount,
                'failed_count' => count($failedDeletions),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            // Log failed bulk deletion attempt
            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'bulk_delete_failed',
                'entity_type' => 'Media',
                'action_summary' => 'Failed to delete media files in bulk',
                'properties' => [
                    'error_message' => $e->getMessage(),
                    'ids_count' => count($ids),
                    'ids' => $ids,
                ],
            ]);

            Log::error('Bulk media deletion error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete media files: ' . $e->getMessage(),
            ], 500);
        }
    }
}
