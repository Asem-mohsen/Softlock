<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Http\UploadedFile;


class FileController extends Controller
{

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
            // $preview  = $this->getPreviewHtml($path, $fileType, $fileName);
            $preview = $this->getPreviewHtml(Storage::url($path), $fileType, $fileName);
            
            return response()->json([
                'name'      => $fileName,
                'size'      => $this->formatFileSize($fileSize),
                'extension' => $fileExtension,
                'preview'   => $preview,
                'tempFilePath' => Storage::url($path)
            ]);
        }

        return response()->json(['error' => 'No file selected.'], 400);
    }

    private function getFileType($extension)
    {
        $extension = strtolower($extension);
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp'];
        $textExtensions  = ['txt', 'md', 'csv'];
        $videoExtensions = ['mp4', 'webm', 'ogg', 'mov', 'avi'];
        
        if (in_array($extension, $imageExtensions)) {
            return 'image';
        } elseif ($extension === 'pdf') {
            return 'pdf';
        } elseif (in_array($extension, $textExtensions)) {
            return 'text';
        } elseif (in_array($extension, $videoExtensions)) {
            return 'video';
        } else {
            return 'other';
        }
    }

    private function getPreviewHtml($path, $fileType, $fileName)
    {
        $storagePath = Storage::url($path);
        $storagePathtext = storage_path('app/public/' . $path);
        
        $storagePath = $path;
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
            case 'video':
                return '<video width="100%" height="auto" controls>
                            <source src="' . $path . '" type="video/' . pathinfo($fileName, PATHINFO_EXTENSION) . '">
                            Your browser does not support the video tag.
                        </video>';
            default:
                return '<img src="' . asset('asset/images/file.png') . '" alt="File Icon" width="100">';
        }
    }

    private function formatFileSize($bytes)
    {
        $units = ['B' , 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function uploadChunk(Request $request)
    {
        $chunk = $request->file('chunk');
        $fileName = $request->input('fileName');
        $chunkNumber = $request->input('chunkNumber');
        $totalChunks = $request->input('totalChunks');

        $tempDir = storage_path('app/chunks/' . $fileName);
        if (!File::isDirectory($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        $chunk->move($tempDir, $chunkNumber);

        if ($chunkNumber == $totalChunks - 1) {
            // All parts received, merge the file
            // file info
            $tempFilePath = $this->mergeChunks($fileName, $totalChunks);
            $fileInfo = pathinfo($tempFilePath);
            $fileName = $fileInfo['filename'];
            $fileSize = filesize($tempFilePath);
            $fileExtension = $fileInfo['extension'];
            $fileType = $this->getFileType($fileExtension);
            $preview = $this->getPreviewHtml(Storage::url('public/temp/' . $fileName . '.' . $fileExtension), $fileType, $fileName);

            return response()->json([
                'success' => 'File uploaded successfully',
                'name' => $fileName,
                'size' => $this->formatFileSize($fileSize),
                'extension' => $fileExtension,
                'preview' => $preview,
                'tempFilePath' => Storage::url('public/temp/' . $fileName . '.' . $fileExtension)
            ]);
        }

        return response()->json(['error' => 'Chunk received']);
    }

    private function mergeChunks($fileName, $totalChunks)
    {
        $tempDir = storage_path('app/chunks/' . $fileName);
        $finalPath = storage_path('app/public/temp/' . $fileName);

        $out = fopen($finalPath, "wb");

        for ($i = 0; $i < $totalChunks; $i++) {
            $in = fopen($tempDir . '/' . $i, "rb");
            stream_copy_to_stream($in, $out);
            fclose($in);
            unlink($tempDir . '/' . $i);
        }

        fclose($out);
        rmdir($tempDir);

        return $finalPath;
    }

    public function encrypt(Request $request)
    {
        if ($request->hasFile('file')) {
            $file          = $request->file('file');
            $fileName      = $file->getClientOriginalName();
            $fileExtension = $file->getClientOriginalExtension();
            $tempPath      = $file->path();
            $encryptedFilePath = $this->encryptData($tempPath);
            $encryptedContent = file_get_contents($encryptedFilePath);
            

            return response()->json([
                'success' =>'File Encrypted Successfilly',
                'encryptedFileName' => $fileName . '.encrypted',
                'encryptedContent' => base64_encode($encryptedContent), 
            ]);
        }

        return response()->json(['error' => 'No file selected.'], 400);
    }

    private function encryptData($filePath)
    {
        $key = openssl_random_pseudo_bytes(32);
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));

        $inputFile = fopen($filePath, 'rb');
        $outputFile = fopen($filePath . '.encrypted', 'wb');
        
        // here we are writtnig the key and IV at the beginning of the file
        $chunkSize = 1024 * 1024;
        fwrite($outputFile, base64_encode($key) . "\n");
        fwrite($outputFile, base64_encode($iv) . "\n");

        while (!feof($inputFile)) {
            $plaintext = fread($inputFile, $chunkSize);
            $ciphertext = openssl_encrypt($plaintext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
            fwrite($outputFile, $ciphertext);
        }
    
        fclose($inputFile);
        fclose($outputFile);
    
        return $filePath . '.encrypted';

    }

    public function decrypt(Request $request)
    {
        $encryptedContent = $request->input('encryptedContent');
        $fileName = $request->input('fileName');
    
        if ($encryptedContent) {
            $decodedContent = $this->decryptData($encryptedContent);
    
            if ($decodedContent !== null) {
                // Encode the content to base64 to safely transmit it
                $base64Content = base64_encode($decodedContent);
                
                return response()->json([
                    'success' => true,
                    'decryptedContent' => $base64Content,
                    'fileName' => $fileName
                ]);
            } else {
                return response()->json(['error' => 'Decryption failed.'], 400);
            }
        }
    
        return response()->json(['error' => 'No encrypted content provided.'], 400);
    }

    private function decryptData($encryptedContent)
    {
        // Decode the encrypted content
        $decodedContent = base64_decode($encryptedContent);

        // Read key and IV from the beginning of the decoded content
        $encryptedFile = fopen('php://memory', 'r+');
        fwrite($encryptedFile, $decodedContent);
        rewind($encryptedFile);

        $key = base64_decode(trim(fgets($encryptedFile)));
        $iv = base64_decode(trim(fgets($encryptedFile)));

        // Prepare to store decrypted content in memory
        $decryptedContent = '';

        // Decrypt the file chunk by chunk
        $chunkSize = 1024 * 1024; // 1MB chunks
        while (!feof($encryptedFile)) {
            $ciphertext = fread($encryptedFile, $chunkSize);
            $plaintext = openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
            $decryptedContent .= $plaintext;
        }

        fclose($encryptedFile);

        return $decryptedContent;
    }
    
}