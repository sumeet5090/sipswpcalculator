<?php

declare(strict_types=1);

namespace Services;

/**
 * SitemapGeneratorInterface
 * Contract for sitemap URL node generation.
 */
interface SitemapGeneratorInterface
{
    /**
     * Generate an array of sitemap URL objects.
     *
     * @return array<int, array{loc: string, lastmod: string, changefreq: string, priority: string, image?: array{loc: string, title: string}}>
     */
    public function generateUrlNodes(): array;
}
