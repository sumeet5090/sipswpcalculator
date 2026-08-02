<?php

declare(strict_types=1);

namespace Controllers;

use Core\ContentManager;
use Core\MetaManager;
use Core\SchemaHelper;
use Core\BlogRepository;
use Core\View;

/**
 * BlogController
 * Handles displaying blog lists and guides content.
 */
class BlogController
{
    private ContentManager $contentManager;
    private MetaManager $metaManager;
    private SchemaHelper $schemaHelper;
    private BlogRepository $blogRepository;
    private \Core\Factories\SchemaFactory $schemaFactory;

    public function __construct(
        ContentManager $contentManager,
        MetaManager $metaManager,
        SchemaHelper $schemaHelper,
        BlogRepository $blogRepository,
        ?\Core\Factories\SchemaFactory $schemaFactory = null
    ) {
        $this->contentManager = $contentManager;
        $this->metaManager = $metaManager;
        $this->schemaHelper = $schemaHelper;
        $this->blogRepository = $blogRepository;

        if ($schemaFactory === null) {
            $this->schemaFactory = new \Core\Factories\SchemaFactory($schemaHelper);
        } else {
            $this->schemaFactory = $schemaFactory;
        }
    }

    public function index(): void
    {
        $all_posts = $this->blogRepository->getAllPosts();
        $categories = $this->blogRepository->getCategories();

        $posts_by_cat = [];
        foreach ($all_posts as $post) {
            $posts_by_cat[$post['category']][] = $post;
        }

        $breadcrumbs_schema = $this->schemaHelper->getBreadcrumbs([
            'Home' => '/',
            'Resources' => '/resources'
        ]);

        View::render('pages/resources', [
            'active_page'  => 'resources.php',
            'all_posts'    => $all_posts,
            'posts_by_cat' => $posts_by_cat,
            'categories'   => $categories,
            'breadcrumbs'  => $breadcrumbs_schema,
        ]);
    }

    public function show(string $category, string $slug): void
    {
        $slug = str_replace('.php', '', $slug);
        $path = "/blog/{$category}/{$slug}";

        $content = $this->contentManager->getParsedContent($path);

        if (!$content) {
            http_response_code(404);
            echo "404 Blog Post Not Found";
            return;
        }

        $post_metadata = null;
        $all_posts = $this->blogRepository->getAllPosts();
        foreach ($all_posts as $post) {
            if (basename($post['href']) === $slug) {
                $post_metadata = $post;
                break;
            }
        }

        $page_config = $this->metaManager->buildFromMetadata($content['metadata'], $slug);

        $breadcrumbs = [
            'Home' => '/',
            'Resources' => '/resources',
            ucfirst($category) => "/resource/{$category}",
            $content['metadata']['title'] ?: ucfirst(str_replace('-', ' ', $slug)) => "/resource/{$category}/{$slug}"
        ];

        // Derive real datePublished from post config (e.g., "March 2026" → "2026-03-01")
        $datePublished = '2026-03-01';
        if ($post_metadata && !empty($post_metadata['date'])) {
            $parsed = \DateTimeImmutable::createFromFormat('F Y', $post_metadata['date']);
            if ($parsed) {
                $datePublished = $parsed->format('Y-m-01');
            }
        }

        // Derive real dateModified from markdown file mtime
        $mdFile = __DIR__ . '/../../content/blog/' . $category . '/' . $slug . '.md';
        $dateModified = file_exists($mdFile)
            ? date('Y-m-d', filemtime($mdFile))
            : $datePublished;

        $page_config['additional_head'] = $this->schemaFactory->generateForPage(
            $category . '/' . $slug,
            'blog',
            $page_config,
            $datePublished,
            [],
            null,
            $breadcrumbs,
            'https://sipswpcalculator.com/resource/' . $category . '/' . $slug
        );

        View::render('layouts/generic-post', [
            'content_html'     => $content['html'],
            'content_metadata' => $content['metadata'],
            'page_config'      => $page_config,
            'post_metadata'    => $post_metadata,
            'category'         => $category,
            'active_page'      => 'blog_post',
            'all_posts'        => $all_posts,
            'date_published'   => $datePublished,
            'date_modified'    => $dateModified,
        ]);
    }
}
