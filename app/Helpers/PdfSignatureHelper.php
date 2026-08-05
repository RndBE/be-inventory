<?php

namespace App\Helpers;

final class PdfSignatureHelper
{
    private const CANVAS_WIDTH = 360;

    private const CANVAS_HEIGHT = 140;

    private const HORIZONTAL_PADDING = 12;

    private const BOTTOM_PADDING = 10;

    private const MINIMUM_CONTENT_WIDTH = 180;

    private const MINIMUM_CONTENT_HEIGHT = 80;

    public static function normalize(?string $relativePath): ?string
    {
        if (!$relativePath) {
            return null;
        }

        $storageRoot = realpath(storage_path('app/public'));
        $normalizedRelativePath = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath), DIRECTORY_SEPARATOR);
        $absolutePath = $storageRoot
            ? realpath($storageRoot.DIRECTORY_SEPARATOR.$normalizedRelativePath)
            : false;
        $fallbackPath = public_path('storage/'.str_replace('\\', '/', ltrim($relativePath, '/\\')));

        if (!$storageRoot || !$absolutePath || !str_starts_with($absolutePath, $storageRoot.DIRECTORY_SEPARATOR)) {
            return is_file($fallbackPath) ? $fallbackPath : null;
        }

        return self::normalizeFile($absolutePath) ?? $fallbackPath;
    }

    public static function normalizeFile(string $absolutePath): ?string
    {
        if (!function_exists('imagecreatetruecolor') || !is_file($absolutePath)) {
            return null;
        }

        $imageInfo = @getimagesize($absolutePath);
        if (!$imageInfo || empty($imageInfo['mime'])) {
            return null;
        }

        $source = match ($imageInfo['mime']) {
            'image/png' => @imagecreatefrompng($absolutePath),
            'image/jpeg' => @imagecreatefromjpeg($absolutePath),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($absolutePath) : false,
            default => false,
        };

        if (!$source) {
            return null;
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        [$minX, $minY, $maxX, $maxY] = self::contentBounds($source, $sourceWidth, $sourceHeight);

        if ($maxX < $minX || $maxY < $minY) {
            imagedestroy($source);

            return null;
        }

        $cropPadding = max(2, (int) round(max($sourceWidth, $sourceHeight) * 0.015));
        $minX = max(0, $minX - $cropPadding);
        $minY = max(0, $minY - $cropPadding);
        $maxX = min($sourceWidth - 1, $maxX + $cropPadding);
        $maxY = min($sourceHeight - 1, $maxY + $cropPadding);
        $cropWidth = $maxX - $minX + 1;
        $cropHeight = $maxY - $minY + 1;

        $availableWidth = self::CANVAS_WIDTH - (self::HORIZONTAL_PADDING * 2);
        $availableHeight = self::CANVAS_HEIGHT - (self::BOTTOM_PADDING * 2);
        $scale = min($availableWidth / $cropWidth, $availableHeight / $cropHeight);
        $destinationWidth = min(
            $availableWidth,
            max(self::MINIMUM_CONTENT_WIDTH, (int) round($cropWidth * $scale))
        );
        $destinationHeight = min(
            $availableHeight,
            max(self::MINIMUM_CONTENT_HEIGHT, (int) round($cropHeight * $scale))
        );
        $destinationX = (int) round((self::CANVAS_WIDTH - $destinationWidth) / 2);
        $destinationY = self::CANVAS_HEIGHT - self::BOTTOM_PADDING - $destinationHeight;

        $canvas = imagecreatetruecolor(self::CANVAS_WIDTH, self::CANVAS_HEIGHT);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 255, 255, 255, 127);
        imagefill($canvas, 0, 0, $transparent);
        imagealphablending($canvas, true);

        imagecopyresampled(
            $canvas,
            $source,
            $destinationX,
            $destinationY,
            $minX,
            $minY,
            $destinationWidth,
            $destinationHeight,
            $cropWidth,
            $cropHeight
        );

        ob_start();
        imagepng($canvas, null, 6);
        $normalizedImage = ob_get_clean();

        imagedestroy($canvas);
        imagedestroy($source);

        return $normalizedImage === false
            ? null
            : 'data:image/png;base64,'.base64_encode($normalizedImage);
    }

    private static function contentBounds(\GdImage $image, int $width, int $height): array
    {
        $minX = $width;
        $minY = $height;
        $maxX = -1;
        $maxY = -1;

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $color = imagecolorsforindex($image, imagecolorat($image, $x, $y));
                $isVisible = $color['alpha'] <= 105;
                $isInk = min($color['red'], $color['green'], $color['blue']) < 242;

                if (!$isVisible || !$isInk) {
                    continue;
                }

                $minX = min($minX, $x);
                $minY = min($minY, $y);
                $maxX = max($maxX, $x);
                $maxY = max($maxY, $y);
            }
        }

        return [$minX, $minY, $maxX, $maxY];
    }
}
