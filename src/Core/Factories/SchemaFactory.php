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
    private HomeSchemaBuilder $homeSchemaBuilder;

    public function __construct(
        SchemaHelper $schemaHelper,
        SiteConfig $siteConfig,
        ?\Core\BlogRepository $blogRepository = null,
        ?ContentManager $contentManager = null,
        ?HomeSchemaBuilder $homeSchemaBuilder = null
    ) {
        $this->schemaHelper = $schemaHelper;
        $this->siteConfig = $siteConfig;
        $this->blogRepository = $blogRepository;
        $this->contentManager = $contentManager;
        $this->homeSchemaBuilder = $homeSchemaBuilder ?? new HomeSchemaBuilder($siteConfig);
    }

    /**
     * Generates home page structured schemas.
     */
    public function generateForHome(array $page_config, array $homeFaqs = [], string $siteModified = \Core\DateConstants::CONTENT_FALLBACK_DATE): string
    {
        $schemas = [];

        // 1. FAQ Schema
        $faqData = $this->formatFaqData($homeFaqs);
        if (!empty($faqData)) {
            $schemas[] = $this->schemaHelper->getFAQ($faqData);
        }

        // 2-7. SoftwareApplication, FinancialProduct, WebSite, Organization, Person, HowTo
        $homeSchemas = $this->homeSchemaBuilder->build($page_config, $homeFaqs, $siteModified);
        foreach ($homeSchemas as $schema) {
            $schemas[] = $schema;
        }

        $html = '';
        foreach ($schemas as $schema) {
            $html .= '<script type="application/ld+json">' . $schema . '</script>' . "\n";
        }

        return $html;
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
                try {
                    $actualModifiedDate = $this->blogRepository->getPostModifiedDate($parts[0], $parts[1]);
                } catch (\Throwable) {
                    $actualModifiedDate = $publishedDate;
                }
            }
        } elseif ($this->contentManager !== null) {
            try {
                $actualModifiedDate = $this->contentManager->getFileModifiedDate('calculators/' . ltrim($slug, '/'));
            } catch (\Throwable) {
                $actualModifiedDate = $publishedDate;
            }
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
        $faqData = $this->formatFaqData($faqs);
        if (!empty($faqData)) {
            $schemas[] = $this->schemaHelper->getFAQ($faqData);
        }

        // 5. Software Application Schema (if Calculator)
        if ($type === 'calculator') {
            $schemaName = (string) ($page_config['schema_name'] ?? $title);
            $rating = is_array($page_config['rating'] ?? null) ? $page_config['rating'] : [
                'ratingValue' => '4.9',
                'ratingCount' => '1280'
            ];
            $software_schema = $this->schemaHelper->getSoftwareApplication(
                $schemaName,
                $description,
                $url,
                "FinanceApplication",
                $rating
            );
            $schemas[] = $software_schema;
        }

        $html = '';
        foreach ($schemas as $schema) {
            $html .= '<script type="application/ld+json">' . $schema . '</script>' . "\n";
        }

        return $html;
    }

    /**
     * Format raw FAQ arrays into key-value pairs [question => answer].
     *
     * @param array $faqs List of FAQ items
     * @return array<string, string>
     */
    private function formatFaqData(array $faqs): array
    {
        if (empty($faqs)) {
            return [];
        }

        $faqData = [];
        foreach ($faqs as $faq) {
            if (isset($faq['q'], $faq['a']) && is_string($faq['q']) && is_string($faq['a'])) {
                $faqData[$faq['q']] = $faq['a'];
            }
        }
        return $faqData;
    }
}
