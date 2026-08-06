<?php

declare(strict_types=1);

namespace Services;

/**
 * HtmlSanitizer
 * Utility service to sanitize text and table HTML inputs for secure rendering.
 */
class HtmlSanitizer
{
    /**
     * Sanitize plain text string with maximum length constraint.
     */
    public function sanitizeText(string $value, int $maxLength): string
    {
        return mb_substr(strip_tags($value), 0, $maxLength);
    }

    /**
     * Extract clean base64 data URI for chart image.
     */
    public function extractChartData(string $chartRaw): string
    {
        $chartRaw = trim($chartRaw);
        if ($chartRaw !== '' && preg_match('/^data:image\/(png|jpeg|gif|webp);base64,/i', $chartRaw)) {
            return $chartRaw;
        }
        return '';
    }

    /**
     * Sanitize HTML table strings allowing safe report tags only.
     */
    public function sanitizeTableHtml(string $tableRaw): string
    {
        if (trim($tableRaw) === '') {
            return '<table><tr><td>No data</td></tr></table>';
        }
        $clean = strip_tags(
            $tableRaw,
            '<table><thead><tbody><tfoot><tr><th><td><caption><colgroup><col><span><strong><em><br>'
        );
        $clean = (string) preg_replace('/\s+on\w+\s*=\s*["\'][^"\']*["\']/i', '', $clean);
        return (string) preg_replace('/\s+style\s*=\s*["\'][^"\']*expression\s*\([^"\']*["\']/i', '', $clean);
    }
}
