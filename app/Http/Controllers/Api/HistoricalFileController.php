<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HistoricalFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HistoricalFileController extends Controller
{
    /**
     * List uploaded historical files.
     */
    public function index(Request $request)
    {
        $files = HistoricalFile::where(
            'user_id',
            $request->user()->id
        )
        ->latest()
        ->get();

        return response()->json([
            'success' => true,
            'message' => 'Historical files retrieved successfully.',
            'data' => $files,
        ]);
    }

    /**
     * Upload a historical CSV file.
     *
     * Files are stored for reference only.
     * They are NOT processed into the profit engine.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:csv,txt',
                'max:10240',
            ],

            'description' => [
                'nullable',
                'string',
                'max:500',
            ],
        ]);

        $file = $request->file('file');

        $path = $file->store(
            'historical-files',
            'local'
        );

        $historicalFile = HistoricalFile::create([
            'user_id' => $request->user()->id,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => 'csv',
            'file_size' => $file->getSize(),
            'description' => $validated['description'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Historical file uploaded successfully.',
            'data' => $historicalFile,
        ], 201);
    }

    /**
     * Display one historical file.
     */
    public function show(
        Request $request,
        HistoricalFile $historicalFile
    ) {
        if ($historicalFile->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Historical file not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Historical file retrieved successfully.',
            'data' => $historicalFile,
        ]);
    }

    /**
     * Download a historical file.
     */
    public function download(
        Request $request,
        HistoricalFile $historicalFile
    ) {
        if ($historicalFile->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Historical file not found.',
            ], 404);
        }

        if (!Storage::disk('local')->exists(
            $historicalFile->file_path
        )) {
            return response()->json([
                'success' => false,
                'message' => 'Historical file no longer exists.',
            ], 404);
        }

        return Storage::disk('local')->download(
            $historicalFile->file_path,
            $historicalFile->file_name
        );
    }

    /**
     * Delete a historical file.
     */
    public function destroy(
        Request $request,
        HistoricalFile $historicalFile
    ) {
        if ($historicalFile->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Historical file not found.',
            ], 404);
        }

        if (Storage::disk('local')->exists(
            $historicalFile->file_path
        )) {
            Storage::disk('local')->delete(
                $historicalFile->file_path
            );
        }

        $historicalFile->delete();

        return response()->json([
            'success' => true,
            'message' => 'Historical file deleted successfully.',
        ]);
    }
}