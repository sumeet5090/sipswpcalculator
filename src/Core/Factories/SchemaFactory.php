<?php

declare(strict_types=1);

namespace Core\Factories;

use Core\ContentManager;
use Core\SchemaHelper;
use Core\SiteConfig;

class SchemaFactory
{
    private SchemaHelper $schemaHelper;
    private SiteConfig $siteConfig;
    private ?\Core\BlogRepository $blogRepository;
    private ?ContentManager $contentManager;

    public function __construct(
        SchemaHelper $schemaHelper,
        SiteConfig $siteConfig,
        ?\Core\BlogRepository $blogRepository = null,
        ?ContentManager $contentManager = null
    ) {
        $this->schemaHelper = $schemaHelper;
        $this->siteConfig = $siteConfig;
        $this->blogRepository = $blogRepository;
        $this->contentManager = $contentManager;
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
        array $customBreadcrumbs = [],
        string $customUrl = ''
    ): string {
        $url = $customUrl ?: $this->siteConfig->getUrl('/' . ltrim($slug, '/'));
        if (empty($page_config['title'])) {
            throw new \Core\Exceptions\ConfigurationException("Missing required 'title' in page configuration for slug: '{$slug}'");
        }
        $title = (string) $page_config['title'];
        $description = $page_config['meta_desc'] ?? $title;
        $imageUrl = $page_config['og_image'] ?? $this->siteConfig->getUrl('/assets/og-image-main.jpg');

        $actualModifiedDate = $publishedDate;
        if ($type === 'blog' && $this->blogRepository !== null) {
            $parts = explode('/', ltrim($slug, '/'));
            if (count($parts) === 2) {
                $actualModifiedDate = $this->blogRepository->getPostModifiedDate($parts[0], $parts[1], $publishedDate);
            }
        } elseif ($this->contentManager !== null) {
            $actualModifiedDate = $this->contentManager->getFileModifiedDate('calculators/' . ltrim($slug, '/'), $publishedDate);
        }

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
            // Extract primary tool name from full title (e.g. "SWP Calculator" from full SEO title)
            // Per architecture constraint: schema name must match URL intent, not full page title.
            $schemaName = preg_split('/\s[-—:]\s/', $title, 2)[0] ?? $title;
            $software_schema = $this->schemaHelper->getSoftwareApplication(
                $schemaName,
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
