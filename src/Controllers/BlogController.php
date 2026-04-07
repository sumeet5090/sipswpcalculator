<?php
namespace Controllers;

use Core\ContentManager;
use Core\MetaManager;
use Core\SchemaHelper;

class BlogController {
    private ContentManager $contentManager;
    private MetaManager $metaManager;
    private SchemaHelper $schemaHelper;

    public function __construct() {
        $this->contentManager = new ContentManager();
        $this->metaManager = new MetaManager();
        $this->schemaHelper = new SchemaHelper();
    }

    public function index() {
        $active_page = 'resources.php';
        require_once __DIR__ . '/../Views/pages/resources.php';
    }

    public function show($category, $slug) {
        $active_page = 'blog_post';
        // Cleanup slug in case it has extension
        $slug = str_replace('.php', '', $slug);
        
        $path = "/blog/{$category}/{$slug}";
        $content = $this->contentManager->getParsedContent($path);
        
        if (!$content) {
            http_response_code(404);
            echo "404 Blog Post Not Found";
            return;
        }

        // Try to get specific meta, or generate dynamic meta from content
        $page_config = $this->metaManager->getMeta($slug);
        if ($page_config['title'] === 'SIP & SWP Calculator 2026: Compounding & Retirement Planner') {
            // Default meta was returned, so we use the title from Markdown front-matter
            $page_config = $this->metaManager->setDynamicMeta(
                $content['metadata']['title'] ?: ucfirst(str_replace('-', ' ', $slug)),
                $content['metadata']['subtitle'] ?: "Read our guide on " . str_replace('-', ' ', $slug)
            );
        }
        
        // Generate breadcrumbs schema
        $breadcrumbs_schema = $this->schemaHelper->getBreadcrumbs([
            'Home' => '/',
            'Resources' => '/resources',
            ucfirst($category) => "/resource/{$category}",
            $content['metadata']['title'] ?: ucfirst(str_replace('-', ' ', $slug)) => "/resource/{$category}/{$slug}"
        ]);

        // Generate Article schema
        $article_schema = $this->schemaHelper->getArticle(
            $page_config['title'],
            '2026-03-01', // Example dates, ideally would come from markdown front-matter
            date('Y-m-d')
        );

        $page_config['additional_head'] = '
            <script type="application/ld+json">' . $breadcrumbs_schema . '</script>
            <script type="application/ld+json">' . $article_schema . '</script>
        ';

        $content_html = $content['html'];
        $content_metadata = $content['metadata'];

        require_once __DIR__ . '/../Views/layouts/generic-post.php';
    }
}
