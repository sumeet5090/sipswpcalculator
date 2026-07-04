<?php

declare(strict_types=1);

namespace Services;

use Core\ContentManager;
use Core\MetaManager;
use Core\SchemaHelper;
use Core\View;

/**
 * GuideRenderer
 * Strategy pattern service to parse, build SEO metadata/schemas, and render educational guides.
 */
class GuideRenderer
{
    private ContentManager $contentManager;
    private MetaManager $metaManager;
    private SchemaHelper $schemaHelper;

    public function __construct(
        ContentManager $contentManager,
        MetaManager $metaManager,
        SchemaHelper $schemaHelper
    ) {
        $this->contentManager = $contentManager;
        $this->metaManager = $metaManager;
        $this->schemaHelper = $schemaHelper;
    }

    /**
     * Parse and render an educational guide template in a standard strategy flow.
     *
     * @param string $slug Guide URL path slug (e.g. 'sip-calculator')
     * @param string $category Category folder name (e.g. 'growth', 'retirement', 'comparison')
     * @param string $publishedDate Meta publication date
     */
    public function render(string $slug, string $category, string $publishedDate): void
    {
        $path = "/calculators/{$slug}";
        $content = $this->contentManager->getParsedContent($path);

        if (!$content) {
            http_response_code(404);
            echo "404 Guide Not Found";
            return;
        }

        $page_config = $this->metaManager->getMeta($slug);
        if (!empty($content['metadata']['title'])) {
            $page_config = $this->metaManager->setDynamicMeta(
                $content['metadata']['title'],
                $content['metadata']['subtitle'] ?: "Read our guide on " . str_replace('-', ' ', $slug)
            );
        }

        // Generate breadcrumbs schema
        $breadcrumbs_schema = $this->schemaHelper->getBreadcrumbs([
            'Home' => '/',
            $page_config['title'] ?? ucfirst(str_replace('-', ' ', $slug)) => '/' . $slug
        ]);

        // Generate Article schema
        $article_schema = $this->schemaHelper->getArticle(
            $page_config['title'],
            $publishedDate,
            $publishedDate
        );

        $page_config['additional_head'] = '
            <link rel="alternate" hreflang="en-IN" href="https://sipswpcalculator.com/' . $slug . '">
            <link rel="alternate" hreflang="x-default" href="https://sipswpcalculator.com/' . $slug . '">
            <script type="application/ld+json">' . $breadcrumbs_schema . '</script>
            <script type="application/ld+json">' . $article_schema . '</script>
        ';

        // Add custom JS script if it matches specific naming/file templates
        $possibleJsPath = '/assets/js/calculators/' . $slug . '.js';
        $fullJsPath = __DIR__ . '/../../' . $possibleJsPath;
        if (file_exists($fullJsPath)) {
            $page_config['scripts'] = [$possibleJsPath];
        }

        $content_html = $content['html'];
        $content_metadata = $content['metadata'];
        $active_page = $slug . '.php';

        View::render('layouts/generic-post', [
            'content_html'     => $content_html,
            'content_metadata' => $content_metadata,
            'page_config'      => $page_config,
            'active_page'      => $active_page,
            'category'         => $category,
        ]);
    }
}
