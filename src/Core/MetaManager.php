<?php

declare(strict_types=1);

namespace Core;

/**
 * MetaManager
 * Encapsulates SEO page metadata generation and metadata mapping.
 */
class MetaManager
{
    private SiteConfig $siteConfig;
    private array $pageMap = [];

    public function __construct(SiteConfig $siteConfig)
    {
        $this->siteConfig = $siteConfig;
        $jsonPath = __DIR__ . '/../../content/meta_pages.json';
        if (file_exists($jsonPath)) {
            $rawJson = (string) file_get_contents($jsonPath);
            $this->pageMap = json_decode($rawJson, true) ?? [];
        }
        if (isset($this->pageMap['home']) && !isset($this->pageMap['home']['canonical'])) {
            $this->pageMap['home']['canonical'] = $this->siteConfig->getUrl('/');
        }
    }

    /**
     * Retrieve pre-configured page metadata by key.
     */
    public function getMeta(string $pageKey): array
    {
        if (isset($this->pageMap[$pageKey])) {
            return $this->pageMap[$pageKey];
        }

        $fallbackTitle = ucfirst(str_replace(['-', '_'], ' ', $pageKey));
        return $this->setDynamicMeta($fallbackTitle !== '' ? $fallbackTitle : 'SIP SWP Calculator', '');
    }

    /**
     * Build SEO metadata array from a markdown content metadata array.
     */
    public function buildFromMetadata(array $metadata, string $fallbackSlug): array
    {
        $title = $metadata['title'] ?? ucfirst(str_replace('-', ' ', $fallbackSlug));
        $desc = $metadata['meta_desc'] ?? $metadata['subtitle'] ?? '';
        $canonical = $metadata['canonical'] ?? $this->siteConfig->getUrl('/' . ltrim($fallbackSlug, '/'));

        return [
            'title'     => $title,
            'meta_desc' => $desc,
            'keywords'  => $metadata['keywords'] ?? '',
            'canonical' => $canonical,
            'og_title'  => $title,
            'og_desc'   => $desc,
        ];
    }

    /**
     * Construct dynamic page metadata array.
     */
    public function setDynamicMeta(string $title, string $desc, ?string $canonical = null): array
    {
        return [
            'title'     => $title,
            'meta_desc' => $desc,
            'keywords'  => '',
            'canonical' => $canonical ?? $this->siteConfig->getUrl('/'),
            'og_title'  => $title,
            'og_desc'   => $desc,
        ];
    }
}
