<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class FileController extends Controller
{

    public function showFile(Request $request)
    {
        $file = $request->file('file');
        $content = $request->input('content');

        if ($file) {
            $fileInfo = pathinfo($file->getClientOriginalName());
            $fileName = $fileInfo['filename'];
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
        $textExtensions  = ['txt', 'md', 'csv'];

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
                if ($content === false) {
                    return '<p>Error: Unable to read the file content.</p>';
                }
                // Debug: Log or display the content
                Log::info('Path: ' . $storagePath);
                return '<pre style="text-align: left;">' . htmlspecialchars($content) . '</pre>';
            default:
                return '<img src="' . asset('images/file-icon.png') . '" alt="File Icon" width="100">';
        }
    }

    public function encrypt(Request $request)
    {
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName      = $file->getClientOriginalName();
            $fileExtension = $file->getClientOriginalExtension();
            $fileContent   = file_get_contents($file->getRealPath());
            $encryptedData = $this->encryptData($fileContent);

            // Extract the key and encrypted content from the $encryptedData
            $encryptedDataParts = explode("\n", $encryptedData);
            $key = str_replace('KEY:', '', $encryptedDataParts[0]);
            $encryptedContent = $encryptedDataParts[2];

            return response()->json([
                'encryptedFileName' => "$fileName.encrypted.$fileExtension",
                'encryptedContent'  => base64_encode($encryptedContent),
                'key'               => $key,
            ]);
        }

        return response()->json(['error' => 'No file selected.'], 400);
    }

    public function decrypt(Request $request)
    {
        $encryptedContent = $request->input('encryptedContent');
        $key = $request->input('key');

        if ($encryptedContent && $key) {
            Log::info('Encryptionkey: ' . $key);
            Log::info('encryptedContent: ' . $encryptedContent);
            $decryptedContent = $this->decryptData($encryptedContent, $key);

            if ($decryptedContent !== null) {
                Log::info('decryptedContent: ' . $encryptedContent);

                return response()->json([
                    'decryptedContent' => base64_encode($decryptedContent),
                ]);
            } else {
                return response()->json(['error' => 'Decryption failed. Make sure the file was encrypted before.'], 400);
            }
        }
        return response()->json(['error' => 'No encrypted content or key provided.'], 400);
    }

    private function encryptData($data)
    {
        $key = openssl_random_pseudo_bytes(32);
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));

        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

        $encryptedContent = 'KEY:' . base64_encode($key) . "\n" . $iv . $encrypted;

        return $encryptedContent;

    }

    private function decryptData($encryptedContent, $key)
    {
        // $key = base64_decode($key);
        // Log::info('decryptionkey: ' . $key);

        // Split the encrypted content into two parts: the IV and the encrypted data
        $iv = substr($encryptedContent, 0, openssl_cipher_iv_length('aes-256-cbc'));
        Log::info('iv: ' . $iv);
        $encrypted = substr($encryptedContent, openssl_cipher_iv_length('aes-256-cbc'));

        Log::info('encrypted: ' . $encrypted);

        // Decrypt the data using the provided key and IV
        $decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

        Log::info('decrypted: ' . $decrypted);
        if ($decrypted !== false) {
            return $decrypted;
        }

        return null;
    }

}
