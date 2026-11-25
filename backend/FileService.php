<?php

namespace App\Services;

class FileService
{
    public function __construct(private readonly string $uploadDirectory)
    {
        $this->ensureDirectoryExists();
    }

    public function listUploads(): array
    {
        $files = [];

        // SKIP_DOTS prevents entries such as "." and ".." from being included
        foreach (new \FilesystemIterator($this->uploadDirectory, \FilesystemIterator::SKIP_DOTS) as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $extension = strtolower($file->getExtension());
            $type = match ($extension) {
                'jpg', 'jpeg', 'png' => 'Afbeelding',
                'xls', 'xlsx', 'csv' => 'Excel',
                'pdf' => 'PDF',
                default => 'Bestand',
            };

            $files[] = [
                'name' => $file->getFilename(),
                'type' => $type,
                'size' => $file->getSize(),
                'modified' => date(DATE_ATOM, $file->getMTime()),
            ];
        }

        usort($files, static fn (array $first, array $second): int =>
            strcmp($second['modified'], $first['modified'])
        );

        return $files;
    }

    public function storeUpload(array $upload): string
    {
        if (!isset($upload['tmp_name'], $upload['name'])
            || !is_string($upload['tmp_name'])
            || !is_string($upload['name'])) {
            throw new \RuntimeException('De uploadgegevens zijn ongeldig.');
        }

        $this->ensureDirectoryExists();

        $filename = $this->createUniqueFilename((string) $upload['name']);
        $targetPath = $this->uploadDirectory . DIRECTORY_SEPARATOR . $filename;

        if (!is_uploaded_file((string) $upload['tmp_name']) || !move_uploaded_file((string) $upload['tmp_name'], $targetPath)) {
            throw new \RuntimeException('Het bestand kon niet worden opgeslagen.');
        }

        return $filename;
    }

    private function ensureDirectoryExists(): void
    {
        if (!is_dir($this->uploadDirectory)
            && !mkdir($this->uploadDirectory, 0755, true)
            && !is_dir($this->uploadDirectory)) {
            throw new \RuntimeException('De uploadmap kon niet worden aangemaakt.');
        }
    }

    private function createUniqueFilename(string $originalName): string
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $randomName = date('ymd_His') . '_' .  bin2hex(random_bytes(16));

        return $extension !== '' ? $randomName . '.' . $extension : $randomName;
    }
}