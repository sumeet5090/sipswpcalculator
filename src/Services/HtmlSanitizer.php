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
     * Extract clean base64 data URI for chart image with strict size constraint.
     */
    public function extractChartData(string $chartRaw, int $maxBytes = 5242880): string
    {
        $chartRaw = trim($chartRaw);
        if ($chartRaw !== '' && strlen($chartRaw) <= $maxBytes && preg_match('/^data:image\/(png|jpeg|gif);base64,[A-Za-z0-9+\/=\s]+$/i', $chartRaw)) {
            return $chartRaw;
        }
        return '';
    }

    /**
     * Sanitize HTML table strings allowing safe report tags only and auto-repairing unbalanced tags.
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
        $clean = (string) preg_replace('/\s+style\s*=\s*["\'][^"\']*(expression|position|fixed|absolute|@import|url\()[^"\']*["\']/i', '', $clean);

        // Auto-balance and repair unclosed/broken HTML table tags via DOMDocument
        $dom = new \DOMDocument();
        $libxmlState = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $clean, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($libxmlState);

        $tables = $dom->getElementsByTagName('table');
        if ($tables->length > 0) {
            $table = $tables->item(0);
            if ($table) {
                return (string) $dom->saveHTML($table);
            }
        }

        return $clean !== '' ? $clean : '<table><tr><td>No data</td></tr></table>';
    }
}
