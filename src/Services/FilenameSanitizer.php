<?php

declare(strict_types=1);

namespace Services;

/**
 * FilenameSanitizer
 * Dedicated utility service to sanitize raw user input strings into safe ASCII and Unicode filenames
 * for HTTP Content-Disposition headers.
 */
class FilenameSanitizer
{
    /**
     * Generate safe ASCII filename and rawurlencoded UTF-8 filename.
     *
     * @param string $rawName Raw user input client/advisor name
     * @param string $prefix File name prefix (e.g., 'Financial_Report_for')
     * @param string $extension File extension (e.g., 'pdf')
     * @return array{filename: string, encodedFilename: string}
     */
    public function sanitizeForAttachment(string $rawName, string $prefix = 'Financial_Report_for', string $extension = 'pdf'): array
    {
        $raw = trim($rawName);
        $unicodeName = preg_replace('/[^\p{L}\p{N}_\- ]/u', '', $raw) ?: 'Client';
        $cleanUnicode = (string) preg_replace('/\s+/', '_', trim($unicodeName));

        $asciiName = (string) preg_replace('/[^a-zA-Z0-9_\-]/', '_', $raw) ?: 'Client';
        $asciiName = (string) preg_replace('/_+/', '_', $asciiName) ?: 'Client';

        $filename = "{$prefix}_{$asciiName}.{$extension}";
        $encodedFilename = rawurlencode("{$prefix}_{$cleanUnicode}.{$extension}");

        return [
            'filename' => $filename,
            'encodedFilename' => $encodedFilename,
        ];
    }
}
