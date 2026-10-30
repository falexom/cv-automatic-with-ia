<?php

namespace App\Support;

use App\Models\Profile;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

final class ProfilePhoto
{
    /**
     * Private local disk; files live at profile-photos/{uuid}.{ext}.
     */
    public const DISK = 'local';

    public const DIRECTORY = 'profile-photos';

    public const MAX_KILOBYTES = 5120;

    public const MAX_BYTES = self::MAX_KILOBYTES * 1024;

    public const MAX_SOURCE_DIMENSION = 8000;

    public const MAX_SOURCE_PIXELS = 24_000_000;

    public const STORED_MAX_DIMENSION = 1600;

    public const DEFAULT_FOCUS = 50;

    public const MIN_ZOOM = 100;

    public const MAX_ZOOM = 200;

    /**
     * Validate the real content of the uploaded image, not only its name
     * or declared MIME type.
     */
    public static function validationError(UploadedFile $file): ?string
    {
        if (! $file->isValid()) {
            return 'No se pudo cargar la fotografía.';
        }

        $size = $file->getSize();

        if (! is_int($size) || $size > self::MAX_BYTES) {
            return 'La fotografía supera el límite de 5 MB.';
        }

        $sourcePath = $file->getRealPath();

        if (! is_string($sourcePath) || ! is_file($sourcePath)) {
            return 'No se pudo leer la fotografía.';
        }

        $info = @getimagesize($sourcePath);

        if ($info === false) {
            return 'El archivo no contiene una imagen válida.';
        }

        if (! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) {
            return 'La fotografía debe ser JPG, JPEG o PNG.';
        }

        $width = (int) $info[0];
        $height = (int) $info[1];

        if ($width < 1
            || $height < 1
            || max($width, $height) > self::MAX_SOURCE_DIMENSION
            || $width * $height > self::MAX_SOURCE_PIXELS) {
            return 'La imagen tiene dimensiones demasiado grandes.';
        }

        $contents = @file_get_contents($sourcePath);
        $image = is_string($contents) ? @imagecreatefromstring($contents) : false;

        if (! $image instanceof GdImage) {
            return 'El archivo no contiene una imagen válida.';
        }

        return null;
    }

    /**
     * Normalize and store a private copy without EXIF metadata.
     *
     * @return array{path: string, mime: string}
     */
    public function store(UploadedFile $file): array
    {
        $error = self::validationError($file);

        if ($error !== null) {
            throw new InvalidArgumentException($error);
        }

        $sourcePath = $file->getRealPath();

        if (! is_string($sourcePath) || ! is_file($sourcePath)) {
            throw new RuntimeException('No se pudo leer la fotografía.');
        }

        $info = @getimagesize($sourcePath);
        $type = is_array($info) ? (int) $info[2] : 0;
        $contents = (string) file_get_contents($sourcePath);
        $image = @imagecreatefromstring($contents);

        if (! $image instanceof GdImage) {
            throw new InvalidArgumentException('El archivo no contiene una imagen válida.');
        }

        $image = $this->orient($image, $type, $sourcePath);
        $image = $this->resize($image);

        [$encoded, $extension, $mime] = $this->encode($image, $type);

        $path = self::DIRECTORY.'/'.Str::uuid()->toString().'.'.$extension;

        if (! Storage::disk(self::DISK)->put($path, $encoded)) {
            throw new RuntimeException('No se pudo guardar la fotografía.');
        }

        return ['path' => $path, 'mime' => $mime];
    }

    public function delete(?string $path): void
    {
        if (is_string($path) && $path !== '') {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    /**
     * Serialize the current photo as a data URI plus its crop. Internal
     * paths are never included in the payload.
     *
     * @return array{data_uri: string, focus_x: int, focus_y: int, zoom: int}|null
     */
    public static function payload(Profile $profile): ?array
    {
        $path = $profile->getAttribute('photo_path');
        $mime = $profile->getAttribute('photo_mime');

        if (! is_string($path)
            || $path === ''
            || ! in_array($mime, ['image/jpeg', 'image/png'], true)) {
            return null;
        }

        $disk = Storage::disk(self::DISK);

        if (! $disk->exists($path)) {
            return null;
        }

        $contents = $disk->get($path);

        if (! is_string($contents) || $contents === '') {
            return null;
        }

        return [
            'data_uri' => 'data:'.$mime.';base64,'.base64_encode($contents),
            'focus_x' => self::clamp(
                (int) ($profile->getAttribute('photo_focus_x') ?? self::DEFAULT_FOCUS),
                0,
                100,
            ),
            'focus_y' => self::clamp(
                (int) ($profile->getAttribute('photo_focus_y') ?? self::DEFAULT_FOCUS),
                0,
                100,
            ),
            'zoom' => self::clamp(
                (int) ($profile->getAttribute('photo_zoom') ?? self::MIN_ZOOM),
                self::MIN_ZOOM,
                self::MAX_ZOOM,
            ),
        ];
    }

    private function orient(GdImage $image, int $type, string $sourcePath): GdImage
    {
        if ($type !== IMAGETYPE_JPEG || ! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($sourcePath);
        $orientation = is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;
        $angle = match ($orientation) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => null,
        };

        if ($angle === null) {
            return $image;
        }

        $rotated = imagerotate($image, $angle, 0);

        if (! $rotated instanceof GdImage) {
            return $image;
        }

        return $rotated;
    }

    private function resize(GdImage $image): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, self::STORED_MAX_DIMENSION / max($width, $height));

        if ($scale >= 1) {
            return $image;
        }

        $newWidth = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));
        $canvas = imagecreatetruecolor($newWidth, $newHeight);

        if (! $canvas instanceof GdImage) {
            return $image;
        }

        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);

        if ($transparent !== false) {
            imagefill($canvas, 0, 0, $transparent);
        }

        imagecopyresampled(
            $canvas,
            $image,
            0,
            0,
            0,
            0,
            $newWidth,
            $newHeight,
            $width,
            $height,
        );

        return $canvas;
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function encode(GdImage $image, int $type): array
    {
        if ($type === IMAGETYPE_PNG) {
            ob_start();
            imagepng($image, null, 6);
            $png = (string) ob_get_clean();

            if ($png !== '' && strlen($png) <= self::MAX_BYTES) {
                return [$png, 'png', 'image/png'];
            }
        }

        $jpeg = $this->jpeg($image, 88);

        if (strlen($jpeg) > self::MAX_BYTES) {
            $jpeg = $this->jpeg($image, 70);
        }

        if (strlen($jpeg) > self::MAX_BYTES) {
            throw new InvalidArgumentException('La fotografía no se pudo preparar dentro del límite de 5 MB.');
        }

        return [$jpeg, 'jpg', 'image/jpeg'];
    }

    private function jpeg(GdImage $image, int $quality): string
    {
        ob_start();
        imagejpeg($image, null, $quality);

        return (string) ob_get_clean();
    }

    private static function clamp(int $value, int $min, int $max): int
    {
        return max($min, min($max, $value));
    }
}
