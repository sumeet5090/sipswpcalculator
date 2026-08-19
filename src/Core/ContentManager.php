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

    public function getFileModifiedDate(string $path): string
    {
        $fullPath = $this->contentDir . '/' . ltrim($path, '/') . '.md';
        if (!file_exists($fullPath)) {
            throw new \RuntimeException("Content markdown file missing at: {$fullPath}");
        }
        return date('Y-m-d', filemtime($fullPath));
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

        // Inject slug IDs and scroll-margin-top into h2 and h3 headings for SSR deep linking & TOC parity
        $html = (string) preg_replace_callback('/<h([23])(?:\s+class="([^"]*)")?>(.*?)<\/h\1>/i', function ($matches) {
            $level = $matches[1];
            $existingClass = (string) $matches[2];
            $text = $matches[3];
            $plainText = strip_tags($text);
            $slug = strtolower(trim((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $plainText), '-'));
            $classes = trim($existingClass . ' scroll-mt-28');
            return "<h{$level} id=\"{$slug}\" class=\"{$classes}\">{$text}</h{$level}>";
        }, $html);

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
        $currentKey = null;

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (empty($trimmed) || str_starts_with($trimmed, '#')) {
                continue;
            }

            if (str_starts_with($trimmed, '-') && $currentKey !== null) {
                $item = trim(substr($trimmed, 1));
                if (preg_match('/^["\'](.*)["\']$/s', $item, $matches)) {
                    $item = $matches[1];
                }
                if (!isset($metadata[$currentKey]) || !is_array($metadata[$currentKey])) {
                    $metadata[$currentKey] = [];
                }
                $metadata[$currentKey][] = $item;
                continue;
            }

            $colonPos = strpos($line, ':');
            if ($colonPos !== false) {
                $key = trim(substr($line, 0, $colonPos));
                $value = trim(substr($line, $colonPos + 1));
                $currentKey = $key;

                if ($value === '') {
                    $metadata[$key] = [];
                    continue;
                }

                if (preg_match('/^["\'](.*)["\']$/s', $value, $matches)) {
                    $value = $matches[1];
                }

                if (strtolower((string) $value) === 'true') {
                    $value = true;
                } elseif (strtolower((string) $value) === 'false') {
                    $value = false;
                }

                $metadata[$key] = $value;
            }
        }

        return $metadata;
    }
}
