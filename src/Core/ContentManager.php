<?php

declare(strict_types=1);

namespace Core;

use Parsedown;

class ContentManager
{
    private Parsedown $parsedown;
    private string $contentDir;

    public function __construct(Parsedown $parsedown, string $contentDir)
    {
        $this->parsedown = $parsedown;
        $this->contentDir = $contentDir;
    }

    public function listMarkdownFiles(string $subDir): array
    {
        $dir = $this->contentDir . '/' . trim($subDir, '/');
        if (!is_dir($dir)) {
            return [];
        }

        $files = glob($dir . '/*.md');
        if (!$files) {
            return [];
        }

        return array_map(fn($f) => basename($f, '.md'), $files);
    }

    public function getFileModifiedDate(string $path, string $fallback = DateConstants::CONTENT_FALLBACK_DATE): string
    {
        $fullPath = $this->contentDir . '/' . ltrim($path, '/') . '.md';
        return file_exists($fullPath) ? date('Y-m-d', filemtime($fullPath)) : $fallback;
    }

    public function getParsedContent(string $path): ?array
    {
        $fullPath = $this->contentDir . '/' . ltrim($path, '/') . '.md';

        if (!file_exists($fullPath)) {
            return null;
        }

        $rawContent = (string) file_get_contents($fullPath);

        $metadata = [];
        $body = $rawContent;

        if (preg_match('/\A\s*---\r?\n(.*?)\r?\n---\r?\n(.*)/s', $rawContent, $matches)) {
            $frontMatter = $matches[1];
            $body = ltrim($matches[2]);
            $metadata = $this->parseFrontMatter($frontMatter);
        }

        $html = $this->parsedown->text($body);

        return [
            'metadata' => $metadata,
            'html' => $html
        ];
    }

    /**
     * Get front-matter metadata only without rendering Markdown HTML.
     */
    public function getMetadataOnly(string $path): ?array
    {
        $fullPath = $this->contentDir . '/' . ltrim($path, '/') . '.md';

        if (!file_exists($fullPath)) {
            return null;
        }

        $rawContent = file_get_contents($fullPath);
        if ($rawContent === false) {
            return null;
        }

        if (preg_match('/\A\s*---\r?\n(.*?)\r?\n---/s', $rawContent, $matches)) {
            return $this->parseFrontMatter($matches[1]);
        }

        return [];
    }

    private function parseFrontMatter(string $frontMatter): array
    {
        $metadata = [];
        $lines = explode("\n", $frontMatter);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            $colonPos = strpos($line, ':');
            if ($colonPos !== false) {
                $key = trim(substr($line, 0, $colonPos));
                $value = trim(substr($line, $colonPos + 1));

                if (preg_match('/^["\'](.*)["\']$/s', $value, $matches)) {
                    $value = $matches[1];
                }

                if (strtolower($value) === 'true') {
                    $value = true;
                } elseif (strtolower($value) === 'false') {
                    $value = false;
                }

                $metadata[$key] = $value;
            }
        }

        return $metadata;
    }
}
