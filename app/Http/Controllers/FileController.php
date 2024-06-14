<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public function showFile(Request $request)
    {
        $file = $request->file('file');

        if ($file) {
            $fileName = $file->getClientOriginalName();
            $fileSize = $file->getSize();
            $fileExtension = $file->getClientOriginalExtension();

            // to store the file in temp storage
            $path     = $file->storeAs('temp', $fileName, 'public');

            $fileType = $this->getFileType($fileExtension);
            $preview  = $this->getPreviewHtml($path, $fileType, $fileName);

            return response()->json([
                'name'      => $fileName,
                'size'      => number_format($fileSize / 1024, 2) . ' KB',
                'extension' => $fileExtension,
                'preview'   => $preview,
            ]);
        }

        return response()->json(['error' => 'No file selected.'], 400);
    }

    private function getFileType($extension)
    {
        $extension = strtolower($extension);
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp'];
        $textExtensions = ['txt', 'md', 'csv'];

        if (in_array($extension, $imageExtensions)) {
            return 'image';
        } elseif ($extension === 'pdf') {
            return 'pdf';
        } elseif (in_array($extension, $textExtensions)) {
            return 'text';
        } else {
            return 'other';
        }
    }

    private function getPreviewHtml($path, $fileType, $fileName)
    {
        $storagePath = Storage::url($path);

        switch ($fileType) {
            case 'image':
                return '<img src="' . $storagePath . '" alt="' . $fileName . '" style="max-width: 100%; max-height: 400px;">';
            case 'pdf':
                return '<iframe src="' . $storagePath . '" width="100%" height="500px"></iframe>';
            case 'text':
                $content = Storage::get($path);
                return '<pre>' . e($content) . '</pre>';
            default:
                return '<img src="' . asset('images/file-icon.png') . '" alt="File Icon" width="100">';
        }
    }

}
