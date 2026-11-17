<?php

namespace Tests\Support\Npis;

use App\Contracts\NpisCloudStorage;
use Illuminate\Http\UploadedFile;

class FakeNpisCloudStorage implements NpisCloudStorage
{
    /**
     * @var array<string, array{url: string, public_id: string, contents: string, file_name: string, folder: string}>
     */
    public array $objects = [];

    private int $counter = 0;

    public function uploadContents(string $contents, string $fileName, string $folder): array
    {
        $this->counter++;
        $publicId = 'npis-test-'.$this->counter;
        $url = 'https://cdn.npis.test/'.$publicId.'/'.$fileName;

        $this->objects[$publicId] = [
            'url' => $url,
            'public_id' => $publicId,
            'contents' => $contents,
            'file_name' => $fileName,
            'folder' => $folder,
        ];

        return ['url' => $url, 'public_id' => $publicId, 'provider' => 'fake'];
    }

    public function uploadFile(UploadedFile $file, string $folder): array
    {
        return $this->uploadContents(
            (string) file_get_contents($file->getRealPath()),
            $file->getClientOriginalName(),
            $folder,
        );
    }

    public function delete(?string $publicId): void
    {
        if ($publicId !== null) {
            unset($this->objects[$publicId]);
        }
    }

    public function fetch(string $url): ?string
    {
        foreach ($this->objects as $object) {
            if ($object['url'] === $url) {
                return $object['contents'];
            }
        }

        return null;
    }

    public function contentsForPublicId(?string $publicId): ?string
    {
        return $publicId !== null ? ($this->objects[$publicId]['contents'] ?? null) : null;
    }

    public function latestContents(): ?string
    {
        if ($this->objects === []) {
            return null;
        }

        return end($this->objects)['contents'];
    }

    public function hasAny(): bool
    {
        return $this->objects !== [];
    }
}
