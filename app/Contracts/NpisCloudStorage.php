<?php

namespace App\Contracts;

use Illuminate\Http\UploadedFile;

interface NpisCloudStorage
{
    /**
     * Upload raw bytes (photos, PDFs, CSVs) to Cloudinary with ImageKit fallback.
     *
     * @return array{url: string, public_id: string, provider: string}
     */
    public function uploadContents(string $contents, string $fileName, string $folder): array;

    /**
     * Upload an HTTP uploaded file (brochures).
     *
     * @return array{url: string, public_id: string, provider: string}
     */
    public function uploadFile(UploadedFile $file, string $folder): array;

    public function delete(?string $publicId): void;

    /**
     * Fetch a stored object through its Cloudinary/ImageKit URL so signed
     * application URLs can stream it without exposing the raw asset URL.
     */
    public function fetch(string $url): ?string;
}
