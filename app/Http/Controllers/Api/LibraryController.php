<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLibraryUploadRequest;
use App\Http\Requests\StoreLibraryUrlRequest;
use App\Jobs\ProcessLibraryItemJob;
use App\Models\LibraryItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LibraryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = $request->user()->libraryItems()
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json(['data' => $items]);
    }

    public function storeFromUpload(StoreLibraryUploadRequest $request): JsonResponse
    {
        $file = $request->file('file');
        $userId = $request->user()->id;

        $path = $file->store("library/{$userId}", config('filesystems.upload'));

        $item = LibraryItem::create([
            'user_id' => $userId,
            'title' => $request->input('title') ?: str_replace('.pdf', '', $file->getClientOriginalName()),
            'source_type' => 'pdf_upload',
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'file_hash' => hash_file('sha256', $file->getRealPath()),
            'content_type' => 'application/pdf',
            'processing_status' => 'pending',
        ]);

        ProcessLibraryItemJob::dispatch($item);

        return response()->json(['data' => $item], 201);
    }

    public function storeFromUrl(StoreLibraryUrlRequest $request): JsonResponse
    {
        $item = LibraryItem::create([
            'user_id' => $request->user()->id,
            'title' => $request->input('title') ?: parse_url($request->input('url'), PHP_URL_HOST) ?? 'Web Article',
            'source_type' => 'url_scrape',
            'source_url' => $request->input('url'),
            'content_type' => 'text/html',
            'processing_status' => 'pending',
        ]);

        ProcessLibraryItemJob::dispatch($item);

        return response()->json(['data' => $item], 201);
    }

    public function show(Request $request, LibraryItem $libraryItem): JsonResponse
    {
        if ($libraryItem->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        return response()->json(['data' => $libraryItem]);
    }

    public function status(Request $request, LibraryItem $libraryItem): JsonResponse
    {
        if ($libraryItem->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        return response()->json([
            'data' => [
                'processing_status' => $libraryItem->processing_status,
                'processing_error' => $libraryItem->processing_error,
            ],
        ]);
    }

    public function destroy(Request $request, LibraryItem $libraryItem): JsonResponse
    {
        if ($libraryItem->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        if ($libraryItem->file_path) {
            Storage::disk(config('filesystems.upload'))->delete($libraryItem->file_path);
        }

        $libraryItem->delete();

        return response()->json(['message' => 'Library item deleted']);
    }
}
