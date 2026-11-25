<?php

namespace App\Services;

use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ImageService
{
    private ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
    }

    public function createVersions(
        string $originalPath,
        string $resizedDirectory,
        string $thumbnailDirectory,
        int $maxWidth = 1200,
        int $maxHeight = 1200
    ): array {
        if (!is_file($originalPath)) {
            throw new \RuntimeException('De originele afbeelding bestaat niet.');
        }

        if ($maxWidth < 1 || $maxWidth > 5000 || $maxHeight < 1 || $maxHeight > 5000) {
            throw new \InvalidArgumentException('De maximale afmetingen moeten tussen 1 en 5000 pixels liggen.');
        }

        $this->createDirectory($resizedDirectory);
        $this->createDirectory($thumbnailDirectory);

        $filename = basename($originalPath);
        $resizedPath = $resizedDirectory . DIRECTORY_SEPARATOR . $filename;
        $thumbnailPath = $thumbnailDirectory . DIRECTORY_SEPARATOR . $filename;

        return [
            'resized' => $resizedPath,
            'thumbnail' => $thumbnailPath,
        ];
    }

    private function createDirectory(string $directory): void
    {
        if (!is_dir($directory)
            && !mkdir($directory, 0755, true)
            && !is_dir($directory)) {
            throw new \RuntimeException('De map kon niet worden aangemaakt: ' . $directory);
        }
    }
}