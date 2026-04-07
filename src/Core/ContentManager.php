<?php
declare(strict_types=1);

namespace Core;

use \Parsedown;

class ContentManager {
    private Parsedown $parsedown;
    private string $contentDir;

    public function __construct(string $contentDir = __DIR__ . '/../../content') {
        $this->parsedown = new Parsedown();
        $this->contentDir = $contentDir;
    }

    public function getParsedContent(string $path): ?array {
        $fullPath = $this->contentDir . '/' . ltrim($path, '/') . '.md';
        
        if (!file_exists($fullPath)) {
            return null;
        }

        $rawContent = file_get_contents($fullPath);
        
        // Simple front-matter parsing (extracting title/subtitle if they exist at the top)
        // Format: # Title\nSubtitle\n---
        $lines = explode("\n", $rawContent);
        $title = '';
        $subtitle = '';
        $contentStartLine = 0;

        if (isset($lines[0]) && strpos($lines[0], '# ') === 0) {
            $title = substr($lines[0], 2);
            $contentStartLine = 1;
            if (isset($lines[1]) && trim($lines[1]) !== '' && trim($lines[1]) !== '---') {
                $subtitle = $lines[1];
                $contentStartLine = 2;
            }
            if (isset($lines[$contentStartLine]) && trim($lines[$contentStartLine]) === '---') {
                $contentStartLine++;
            }
        }

        $body = implode("\n", array_slice($lines, $contentStartLine));
        $html = $this->parsedown->text($body);

        return [
            'metadata' => [
                'title' => $title,
                'subtitle' => $subtitle,
            ],
            'html' => $html
        ];
    }
}
