<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Http\UploadedFile;


class FileController extends Controller
{

    private function createTemporaryFile($filePath)
    {
        $tempFile = new UploadedFile(
            $filePath,
            pathinfo($filePath, PATHINFO_BASENAME),
            mime_content_type($filePath),
            null,
            true
        );

        return $tempFile;
    }

    public function showFile(Request $request)
    {
        $tempFilePath = $request->input('tempFilePath');
        if ($tempFilePath) {

            $fileInfo = pathinfo($tempFilePath);
            $fileName = $fileInfo['filename'];
            $fileSize =  filesize(public_path(str_replace(url('/'), '', $tempFilePath)));
            $fileExtension = $fileInfo['extension'];
            $fileType = $this->getFileType($fileExtension);
            $preview = $this->getPreviewHtml($tempFilePath, $fileType, $fileName);

            return response()->json([
                'name' => $fileName,
                'size' => number_format($fileSize / 1024, 2) . ' KB',
                'extension' => $fileExtension,
                'preview' => $preview,
                'tempFilePath' => $tempFilePath
            ]);
        }

        $file = $request->file('file');
        $content = $request->input('content');
        if ($file) {
            $fileInfo = pathinfo($file->getClientOriginalName());
            $fileName = $fileInfo['filename'];
            $fileSize = $file->getSize();
            $fileExtension = $file->getClientOriginalExtension();

            $fileNameWithExtension = $fileName . '.' . $fileExtension;

            // to store the file in temp storage
            $path     = $file->storeAs('temp', $fileNameWithExtension, 'public');

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
        $storagePathtext = storage_path('app/public/' . $path);

        switch ($fileType) {
            case 'image':
                return '<img src="' . $storagePath . '" alt="' . $fileName . '" style="max-width: 100%; max-height: 400px;">';
            case 'pdf':
                return '<iframe src="' . $storagePath . '" width="100%" height="500px"></iframe>';
            case 'text':
                if(file_exists($storagePathtext)){
                    $content = file_get_contents($storagePathtext);
                }else{
                    $content = file_get_contents($path);
                }
                $limit = 1200;
                if (strlen($content) > $limit) {
                    $content = substr($content, 0, $limit) . '...';
                }
                return '<pre style="text-align: left;">' . htmlspecialchars($content) . '</pre>';
            default:
                return '<img src="' . asset('asset/images/file.png') . '" alt="File Icon" width="100">';
        }
    }

    public function encrypt(Request $request)
    {
        if ($request->hasFile('file')) {
            $file          = $request->file('file');
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
                'encryptedContent'  =>  $encryptedData ,
                'key'               => $key,
            ]);
        }

        return response()->json(['error' => 'No file selected.'], 400);
    }

    public function decrypt(Request $request)
    {
        $encryptedContent = $request->input('encryptedContent');
        $fileExtension = $request->input('extension');

        if ($encryptedContent) {
            $decryptedContent = $this->decryptData($encryptedContent);

            if ($decryptedContent !== null) {
                $tempPath = storage_path('app/temp');
                $storageTempPath = storage_path('app/public/temp');

                // Create the 'temp' directory if it doesn't exist
                if (!File::isDirectory($tempPath)) {
                    File::makeDirectory($tempPath, 0755, true, true);
                }

                $tempFilePath = $tempPath . '/decrypted_file.' . $fileExtension;
                $publicFilePath = $storageTempPath . '/decrypted_file.' . $fileExtension;

                file_put_contents($tempFilePath, $decryptedContent);

                // Copy the file to the public/temp directory
                File::copy($tempFilePath, $publicFilePath);
                $url = asset('storage/temp/decrypted_file.' . $fileExtension);
                return response()->json([
                    'success' => true,
                    'message' => 'File decrypted successfully.',
                    'tempFilePath' => $url,
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

        // Encode key and IV for easy storage and transmission
        $encodedKey = base64_encode($key);
        $encodedIv = base64_encode($iv);
        $encodedEncryptedData = base64_encode($encrypted);

        $encryptedContent = 'KEY:' . $encodedKey . "\n" . 'IV:' . $encodedIv . "\n" . $encodedEncryptedData;

        return $encryptedContent;

    }

    private function decryptData($encryptedContent)
    {
        // Split the encrypted content to extract the IV and encrypted data
        $encryptedContentParts = explode("\n", $encryptedContent);
        $key = base64_decode(str_replace('KEY:', '', $encryptedContentParts[0]));
        $iv = base64_decode(trim(str_replace('IV:', '', $encryptedContentParts[1])));
        $encrypted = base64_decode($encryptedContentParts[2]);

        // Decrypt the data using the provided key and IV
        $decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

        return $decrypted !== false ? $decrypted : null;

    }

}
