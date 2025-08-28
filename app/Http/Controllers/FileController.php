<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class FileController extends Controller
{
    /**
     * Upload a file to temporary storage
     */
    public function uploadTemp(Request $request)
    {
        $allowedCollections = [
            'default',
            'avatar',
            'images',
            'documents',
            'carte_grise_document',
            'assurance_documents',
            'vignette_documents',
            'carburant_documents'
        ];

        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:10240',
            'collection' => 'required|string|in:' . implode(',', $allowedCollections),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('file');
            $collection = $request->input('collection');

            // Store with visibility settings
            $path = $file->storeAs(
                "temp/{$collection}",
                Str::uuid() . '.' . $file->getClientOriginalExtension(),
                ['visibility' => 'public']
            );

            return response()->json([
                'success' => true,
                'name' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'collection_name' => $collection
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload file',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clean up temporary files
     */
    public function cleanupTemp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'paths' => 'required|array',
            'paths.*' => 'string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            foreach ($request->input('paths') as $path) {
                if (Storage::exists($path)) {
                    Storage::delete($path);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Temporary files cleaned up'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cleanup failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getFile($path)
    {
        $filePath = storage_path('app/public/' . $path);

        if (!file_exists($filePath)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        return response()->file($filePath);
    }

}
