<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function index()
    {
        $media = Media::latest()->take(18)->get();
        return view('media.index', compact('media'));
    }

    public function lazyLoad(Request $request)
    {
        $offset = $request->input('offset', 0);
        $limit = 18;

        $media = Media::latest()->skip($offset)->take($limit)->get();

        return response()->json([
            'media' => $media,
        ]);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'file|mimes:jpeg,png,jpg,gif,svg,pdf|max:10240',
        ]);

        $uploadedMedia = [];

        foreach ($request->file('files') as $file) {
            $path = $file->store('media', 'public');

            $media = Media::create([
                'name' => $file->getClientOriginalName(),
                'file_name' => basename($path),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'disk' => 'public',
                'folder' => 'media',
            ]);

            $uploadedMedia[] = $media;
        }

        return response()->json([
            'success' => true,
            'message' => 'Files uploaded successfully!',
            'media' => $uploadedMedia,
        ]);
    }

    public function bulkDelete(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'ids' => 'required|array', // Ensure that IDs are passed as an array
            'ids.*' => 'exists:media,id' // Validate that each ID exists in the media table
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
