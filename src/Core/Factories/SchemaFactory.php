<?php

declare(strict_types=1);

namespace Core\Factories;

use Core\SchemaHelper;
use Core\SiteConfig;
use Core\Strategies\CalculatorStrategyInterface;

class SchemaFactory
{
    private SchemaHelper $schemaHelper;
    private SiteConfig $siteConfig;

    public function __construct(SchemaHelper $schemaHelper, ?SiteConfig $siteConfig = null)
    {
        $this->schemaHelper = $schemaHelper;
        $this->siteConfig = $siteConfig ?? new SiteConfig();
    }

    /**
     * Generates all necessary schemas for a page.
     */
    public function generateForPage(
        string $slug,
        string $type,
        array $page_config,
        string $publishedDate,
        array $faqs = [],
        ?CalculatorStrategyInterface $strategy = null,
        array $customBreadcrumbs = [],
        string $customUrl = ''
    ): string {
        $url = $customUrl ?: $this->siteConfig->getUrl('/' . ltrim($slug, '/'));
        $title = $page_config['title'] ?? ucfirst(str_replace('-', ' ', basename($slug)));
        $description = $page_config['meta_desc'] ?? $title;
        $imageUrl = $page_config['og_image'] ?? $this->siteConfig->getUrl('/assets/og-image-main.jpg');

        $mdFile = '';
        if ($type === 'blog') {
            $mdFile = __DIR__ . '/../../../content/blog/' . ltrim($slug, '/');
            if (!str_ends_with($mdFile, '.md')) {
                $mdFile .= '.md';
            }
        } else {
            $mdFile = __DIR__ . '/../../../content/calculators/' . ltrim($slug, '/') . '.md';
        }
        $actualModifiedDate = file_exists($mdFile) ? date('Y-m-d', filemtime($mdFile)) : $publishedDate;

        $schemas = [];

        // 1. Breadcrumbs
        $breadcrumbs = $customBreadcrumbs ?: [
            'Home' => '/',
            $title => '/' . ltrim($slug, '/')
        ];
        $schemas[] = $this->schemaHelper->getBreadcrumbs($breadcrumbs);

        // 2. Article Schema
        $schemas[] = $this->schemaHelper->getArticle(
            $title,
            $description,
            $url,
            $imageUrl,
            $publishedDate,
            $actualModifiedDate
        );

        // 3. WebPage Schema
        $schemas[] = $this->schemaHelper->getWebPage(
            $title,
            $description,
            $url
        );

        // 4. FAQ Schema
        if (!empty($faqs)) {
            $faqData = [];
            foreach ($faqs as $faq) {
                if (isset($faq['q']) && isset($faq['a'])) {
                    $faqData[$faq['q']] = $faq['a'];
                }
            }
            if (!empty($faqData)) {
                $schemas[] = $this->schemaHelper->getFAQ($faqData);
            }
        }

        // 5. Software Application Schema (if Calculator)
        if ($type === 'calculator') {
            $software_schema = $this->schemaHelper->getSoftwareApplication(
                $title,
                $description,
                $url,
                "FinanceApplication"
            );
            $schemas[] = $software_schema;
        }

        $html = '';
        foreach ($schemas as $schema) {
            $html .= '<script type="application/ld+json">' . $schema . '</script>' . "\n";
        }

        return $html;
    }
}
