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
        $this->initPageMap();
    }

    private function initPageMap(): void
    {
        $this->pageMap = [
            'home' => [
                'title'     => 'SIP and SWP Calculator Together | Combined Mutual Fund Planner India 2026',
                'meta_desc' => 'Free SIP and SWP calculator combined in one tool. Plan your mutual fund journey — from step-up SIP wealth accumulation to SWP retirement withdrawals with charts and PDF reports.',
                'keywords'  => 'sip and swp calculator together, sip swp combo calculator, sip swp combined calculator, sip to swp calculator, step up sip calculator, swp calculator india, mutual fund sip swp calculator, swp planner, investment planner, retirement planning, sip calculator india, mutual fund return calculator',
                'canonical' => $this->siteConfig->getUrl('/'),
                'og_title'  => 'SIP and SWP Calculator Together | Combined Mutual Fund Planner India 2026',
                'og_desc'   => 'Free SIP and SWP calculator combined in one tool. Plan your mutual fund journey — from step-up SIP wealth accumulation to SWP retirement withdrawals with charts and PDF reports.',
            ],
        ];
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
