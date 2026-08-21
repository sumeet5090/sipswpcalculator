<?php

declare(strict_types=1);

namespace Services;

use RuntimeException;

/**
 * FileUploadService
 * Handles validation and base64 data URI encoding for uploaded image files.
 */
class FileUploadService
{
    /**
     * Process an uploaded logo image file into a safe Base64 Data URI string.
     */
    public function processLogoUpload(?array $logoFile): ?string
    {
        if ($logoFile === null || ($logoFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $tmpName = (string) ($logoFile['tmp_name'] ?? '');
        $fileSize = (int) ($logoFile['size'] ?? 0);

        if (empty($tmpName) || !file_exists($tmpName)) {
            throw new RuntimeException('Uploaded file does not exist.');
        }

        if ($fileSize > 2 * 1024 * 1024) {
            throw new RuntimeException('Logo file too large. Maximum 2MB allowed.');
        }

        $imageInfo = getimagesize($tmpName);
        if ($imageInfo === false) {
            throw new RuntimeException('Uploaded file is not a valid image.');
        }

        $allowedTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF];
        if (!in_array($imageInfo[2], $allowedTypes, true)) {
            throw new RuntimeException('Invalid image type. Only JPEG, PNG, and GIF are allowed for PDF report generation.');
        }

        if ($imageInfo[0] > 2000 || $imageInfo[1] > 2000) {
            throw new RuntimeException('Image dimensions too large. Maximum 2000x2000 pixels.');
        }

        $safeMime = $imageInfo['mime'];
        $data = file_get_contents($tmpName);
        if ($data === false) {
            throw new RuntimeException('Failed to read uploaded image file.');
        }

        // If GD is available, re-encode image to strip EXIF metadata and neutralize polyglots
        if (function_exists('imagecreatefromstring')) {
            try {
                $img = imagecreatefromstring($data);
            } catch (\Throwable) {
                $img = false;
            }
            if ($img !== false) {
                ob_start();
                if ($imageInfo[2] === IMAGETYPE_PNG) {
                    imagepng($img);
                    $safeMime = 'image/png';
                } elseif ($imageInfo[2] === IMAGETYPE_GIF) {
                    imagegif($img);
                    $safeMime = 'image/gif';
                } else {
                    imagejpeg($img, null, 90);
                    $safeMime = 'image/jpeg';
                }
                $cleanData = ob_get_clean();
                if ($cleanData !== '') {
                    $data = $cleanData;
                }
            }
        }

        return 'data:' . $safeMime . ';base64,' . base64_encode($data);
    }
}
