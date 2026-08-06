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
        } else {
            // Legacy fallback if no front-matter is used
            $lines = explode("\n", $rawContent);
            $title = '';
            $subtitle = '';
            $contentStartLine = 0;

            if (str_starts_with($lines[0], '# ')) {
                $title = substr($lines[0], 2);
                $contentStartLine = 1;
                if (isset($lines[1]) && trim($lines[1]) !== '' && trim($lines[1]) !== '---') {
                    $subtitle = trim($lines[1]);
                    $contentStartLine = 2;
                }
                if (isset($lines[$contentStartLine]) && trim($lines[$contentStartLine]) === '---') {
                    $contentStartLine++;
                }
                $metadata['title'] = trim($title);
                $metadata['subtitle'] = $subtitle;
                $body = implode("\n", array_slice($lines, $contentStartLine));
            }
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

        $rawContent = (string) file_get_contents($fullPath);

        if (preg_match('/\A\s*---\r?\n(.*?)\r?\n---/s', $rawContent, $matches)) {
            return $this->parseFrontMatter($matches[1]);
        }

        $parsed = $this->getParsedContent($path);
        return $parsed['metadata'] ?? null;
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
                } elseif (is_numeric($value) && !str_starts_with($value, '0') && strlen($value) < 10) {
                    $value = str_contains($value, '.') ? (float) $value : (int) $value;
                }

                $metadata[$key] = $value;
            }
        }

        return $metadata;
    }
}
