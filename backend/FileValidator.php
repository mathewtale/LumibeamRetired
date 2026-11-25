<?php

namespace App\Validator;

class FileValidator
{
    private const ALLOWED_MIME_TYPES = [
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'pdf' => ['application/pdf'],
    ];
    private const MAX_FILE_SIZE = 2 * 1024 * 1024;

    public function validateUpload(array $upload): array
    {
        $errors = [];

        if (!isset($upload['error'], $upload['name'], $upload['tmp_name'])) {
            return ['Er is geen geldig bestand geüpload.'];
        }

        if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['De upload is mislukt. Probeer het opnieuw.'];
        }

        if (!is_string($upload['name']) || !is_string($upload['tmp_name'])) {
            return ['De uploadgegevens zijn ongeldig.'];
        }

        $extension = strtolower(pathinfo((string) $upload['name'], PATHINFO_EXTENSION));
        if (!array_key_exists($extension, self::ALLOWED_MIME_TYPES)) {
            $errors[] = 'Bestandstype is niet toegestaan. Gebruik JPG, PNG of PDF.';
        }

        $size = $upload['size'] ?? 0;
        if (!is_numeric($size) || (int) $size > self::MAX_FILE_SIZE) {
            $errors[] = 'Het bestand is te groot. Maximaal 2 MB.';
        }

        if (!is_uploaded_file((string) $upload['tmp_name'])) {
            $errors[] = 'Het bestand lijkt niet veilig te zijn.';
        }

        if (array_key_exists($extension, self::ALLOWED_MIME_TYPES)) {
            $temporaryPath = (string) $upload['tmp_name'];
            if (!is_file($temporaryPath) || !is_readable($temporaryPath)) {
                $errors[] = 'De inhoud van het bestand komt niet overeen met de bestandsextensie.';
            } else {
                $mimeType = $this->detectMimeType($temporaryPath);

                if ($mimeType === false || !in_array($mimeType, self::ALLOWED_MIME_TYPES[$extension], true)) {
                    $errors[] = 'De inhoud van het bestand komt niet overeen met de bestandsextensie.';
                }
            }
        }

        return $errors;
    }

    private function detectMimeType(string $path): string|false
    {
        if (class_exists(\finfo::class)) {
            $fileInfo = new \finfo(FILEINFO_MIME_TYPE);
            return $fileInfo->file($path);
        }

        $image = @getimagesize($path);
        if (is_array($image) && isset($image['mime'])) {
            return $image['mime'];
        }

        $header = file_get_contents($path, false, null, 0, 1024);
        if ($header !== false && preg_match('/%PDF-[0-9]\.[0-9]/', $header) === 1) {
            return 'application/pdf';
        }

        return false;
    }
}