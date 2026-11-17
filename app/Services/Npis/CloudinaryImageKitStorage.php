<?php

namespace App\Services\Npis;

use App\Contracts\NpisCloudStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use ImageKit\ImageKit;
use RuntimeException;
use Throwable;

/**
 * Mirrors the existing application upload pattern:
 * Cloudinary first, ImageKit as the fallback. No local persistence is used.
 */
class CloudinaryImageKitStorage implements NpisCloudStorage
{
    public function uploadContents(string $contents, string $fileName, string $folder): array
    {
        $mime = $this->mimeType($contents, $fileName);
        $cloudinaryFile = 'data:'.$mime.';base64,'.base64_encode($contents);

        try {
            $response = cloudinary()->uploadApi()->upload($cloudinaryFile, [
                'folder' => $folder,
                'resource_type' => 'auto',
            ]);

            return [
                'url' => (string) $response['secure_url'],
                'public_id' => (string) $response['public_id'],
                'provider' => 'cloudinary',
            ];
        } catch (Throwable) {
            return $this->uploadContentsToImageKit($contents, $fileName, $folder, $mime);
        }
    }

    public function uploadFile(UploadedFile $file, string $folder): array
    {
        try {
            $response = cloudinary()->uploadApi()->upload($file->getRealPath(), [
                'folder' => $folder,
                'resource_type' => 'auto',
            ]);

            return [
                'url' => (string) $response['secure_url'],
                'public_id' => (string) $response['public_id'],
                'provider' => 'cloudinary',
            ];
        } catch (Throwable) {
            $upload = uploadToImageKit($file, $folder);

            if (empty($upload['url']) || empty($upload['public_id'])) {
                throw new RuntimeException('Cloudinary and ImageKit both rejected the upload.');
            }

            return [
                'url' => $upload['url'],
                'public_id' => $upload['public_id'],
                'provider' => 'imagekit',
            ];
        }
    }

    public function delete(?string $publicId): void
    {
        if ($publicId === null || trim($publicId) === '') {
            return;
        }

        deleteOldFile($publicId);
    }

    public function fetch(string $url): ?string
    {
        if (! Str::startsWith($url, ['http://', 'https://'])) {
            return null;
        }

        $response = Http::timeout(20)->get($url);

        return $response->successful() ? $response->body() : null;
    }

    private function uploadContentsToImageKit(string $contents, string $fileName, string $folder, string $mime): array
    {
        $imageKit = new ImageKit(
            (string) config('services.imagekit.public_key'),
            (string) config('services.imagekit.private_key'),
            (string) config('services.imagekit.endpoint_key'),
        );

        $response = $imageKit->upload([
            'file' => 'data:'.$mime.';base64,'.base64_encode($contents),
            'fileName' => $fileName,
            'folder' => $folder,
        ]);

        if (empty($response->result->url) || empty($response->result->fileId)) {
            throw new RuntimeException('ImageKit rejected the upload.');
        }

        return [
            'url' => (string) $response->result->url,
            'public_id' => (string) $response->result->fileId,
            'provider' => 'imagekit',
        ];
    }

    private function mimeType(string $contents, string $fileName): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        if ($finfo !== false) {
            $mime = finfo_buffer($finfo, $contents);
            finfo_close($finfo);

            if (is_string($mime) && $mime !== '') {
                return $mime;
            }
        }

        return match (strtolower(pathinfo($fileName, PATHINFO_EXTENSION))) {
            'pdf' => 'application/pdf',
            'csv' => 'text/csv',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };
    }
}
