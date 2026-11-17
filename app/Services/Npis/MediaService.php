<?php

namespace App\Services\Npis;

use App\Contracts\NpisCloudStorage;
use App\Exceptions\NpisApiException;
use App\Models\Npis\Badge;
use App\Models\Npis\Registration;
use Illuminate\Support\Facades\URL;

class MediaService
{
    public function __construct(private readonly NpisCloudStorage $cloud) {}

    /**
     * Store a participant or desk photo after re-encoding it. Re-encoding with
     * GD strips EXIF/GPS metadata and normalises the file type.
     */
    public function storePhoto(Registration $registration, string $dataUrl, string $source): Badge
    {
        if (! preg_match('/^data:image\/(jpeg|png|webp);base64,[A-Za-z0-9+\/]+=*$/', $dataUrl)) {
            throw NpisApiException::validation('Upload a JPEG, PNG or WebP photo.', ['photo' => 'Upload a JPEG, PNG or WebP photo.']);
        }

        if (strlen($dataUrl) > (int) config('npis.media.photo_max_bytes')) {
            throw NpisApiException::validation(
                'This photo is too large. Use one under 300 KB.',
                ['photo' => 'This photo is too large. Use one under 300 KB.']
            );
        }

        $binary = base64_decode(substr($dataUrl, strpos($dataUrl, ',') + 1), true);

        if ($binary === false) {
            throw NpisApiException::validation('The photo could not be read.', ['photo' => 'The photo could not be read.']);
        }

        $info = @getimagesizefromstring($binary);

        if ($info === false || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            throw NpisApiException::validation('Upload a JPEG, PNG or WebP photo.', ['photo' => 'Upload a JPEG, PNG or WebP photo.']);
        }

        $maxDimension = (int) config('npis.media.photo_max_dimension');
        if ($info[0] > $maxDimension || $info[1] > $maxDimension) {
            throw NpisApiException::validation(
                'The photo dimensions are too large.',
                ['photo' => 'The photo dimensions are too large.']
            );
        }

        $image = @imagecreatefromstring($binary);

        if (! $image) {
            throw NpisApiException::validation('The photo could not be processed.', ['photo' => 'The photo could not be processed.']);
        }

        $normalized = imagecreatetruecolor(imagesx($image), imagesy($image));
        $white = imagecolorallocate($normalized, 255, 255, 255);
        imagefill($normalized, 0, 0, $white);
        imagecopy($normalized, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
        imagedestroy($image);

        ob_start();
        imagejpeg($normalized, null, 86);
        $jpeg = (string) ob_get_clean();
        imagedestroy($normalized);

        $badge = app(BadgeService::class)->ensureBadge($registration);

        if ($badge->photo_public_id) {
            $this->cloud->delete($badge->photo_public_id);
        }

        $upload = $this->cloud->uploadContents(
            $jpeg,
            $registration->id.'.jpg',
            config('npis.storage.folders.photos', 'npis/photos'),
        );

        $badge->forceFill([
            'photo_path' => null,
            'photo_url' => $upload['url'],
            'photo_public_id' => $upload['public_id'],
            'photo_source' => $source,
            'photo_uploaded_at' => now(),
        ])->save();

        return $badge;
    }

    public function removePhoto(Registration $registration): Badge
    {
        $badge = app(BadgeService::class)->ensureBadge($registration);

        if ($badge->photo_public_id) {
            $this->cloud->delete($badge->photo_public_id);
        }

        $badge->forceFill([
            'photo_path' => null,
            'photo_url' => null,
            'photo_public_id' => null,
            'photo_source' => null,
            'photo_uploaded_at' => null,
        ])->save();

        return $badge;
    }

    public function photoUrl(Registration $registration, Badge $badge): ?string
    {
        if (! $badge->photo_url) {
            return null;
        }

        return URL::temporarySignedRoute(
            'npis.media.photo',
            now()->addSeconds((int) config('npis.media.photo_ttl', 900)),
            ['registration' => $registration->id],
        );
    }
}
