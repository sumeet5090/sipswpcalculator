<?php

declare(strict_types=1);

namespace Core;

use Services\ConfigService;

/**
 * MetaManager
 * Encapsulates SEO page metadata generation and metadata mapping.
 */
class MetaManager
{
    private SiteConfig $siteConfig;
    private ?array $pageMap = null;
    private string $metaPagesPath;

    public function __construct(
        SiteConfig $siteConfig,
        ?string $metaPagesPath = null
    ) {
        $this->siteConfig = $siteConfig;
        $this->metaPagesPath = $metaPagesPath ?? 'content/meta_pages.json';
    }

    private function loadPageMap(): void
    {
        if ($this->pageMap !== null) {
            return;
        }

        $fullPath = (str_starts_with($this->metaPagesPath, '/') || (DIRECTORY_SEPARATOR === '\\' && str_contains($this->metaPagesPath, ':')))
            ? $this->metaPagesPath
            : __DIR__ . '/../../' . ltrim($this->metaPagesPath, '/');

        if (!file_exists($fullPath)) {
            throw new \Core\Exceptions\ConfigurationException("Metadata pages configuration missing at: {$fullPath}");
        }

        $rawJson = file_get_contents($fullPath);
        if ($rawJson === false) {
            throw new \Core\Exceptions\ConfigurationException("Failed to read metadata pages configuration at: {$fullPath}");
        }

        $decoded = json_decode($rawJson, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            throw new \Core\Exceptions\ConfigurationException("Failed to parse metadata pages configuration: " . json_last_error_msg());
        }

        $this->pageMap = $decoded;
    }

    /**
     * Retrieve pre-configured page metadata by key.
     */
    public function getMeta(string $pageKey): array
    {
        $this->loadPageMap();

        $key = trim($pageKey, '/');
        if ($key === '') {
            $key = 'home';
        }

        if (isset($this->pageMap[$key])) {
            $meta = $this->pageMap[$key];
            if ($key === 'home' && !isset($meta['canonical'])) {
                $meta['canonical'] = $this->siteConfig->getUrl('/');
            }
            return $meta;
        }

        if (isset($this->pageMap[$pageKey])) {
            $meta = $this->pageMap[$pageKey];
            if ($pageKey === 'home' && !isset($meta['canonical'])) {
                $meta['canonical'] = $this->siteConfig->getUrl('/');
            }
            return $meta;
        }

        $fallbackTitle = ucfirst(str_replace(['-', '_'], ' ', $key));
        return $this->setDynamicMeta($fallbackTitle, '', $this->siteConfig->getUrl('/' . $key));
    }

    /**
     * Build SEO metadata array from a markdown content metadata array.
     */
    public function buildFromMetadata(array $metadata, string $urlPath): array
    {
        if (!str_starts_with($urlPath, '/')) {
            throw new \InvalidArgumentException("URL path must start with a slash: {$urlPath}");
        }

        $title = $metadata['title'] ?? ucfirst(str_replace('-', ' ', basename($urlPath)));
        $desc = $metadata['meta_desc'] ?? $metadata['subtitle'] ?? '';
        $canonical = $metadata['canonical'] ?? $this->siteConfig->getUrl($urlPath);
        $ogImage = $metadata['og_image'] ?? $this->siteConfig->getUrl('/assets/og-image-main.jpg');
        if (is_string($ogImage) && !str_starts_with($ogImage, 'http')) {
            $ogImage = $this->siteConfig->getUrl($ogImage);
        }

        return [
            'title' => $title,
            'meta_desc' => $desc,
            'keywords' => $metadata['keywords'] ?? '',
            'canonical' => $canonical,
            'og_title' => $title,
            'og_desc' => $desc,
            'og_image' => $ogImage,
        ];
    }

    /**
     * Construct dynamic page metadata array.
     */
    public function setDynamicMeta(string $title, string $desc, ?string $canonical = null): array
    {
        return [
            'title' => $title,
            'meta_desc' => $desc,
            'keywords' => '',
            'canonical' => $canonical ?? $this->siteConfig->getUrl('/'),
            'og_title' => $title,
            'og_desc' => $desc,
        ];
    }
}
