<?php

declare(strict_types=1);

namespace Controllers;

use Core\BlogRepository;
use Core\ContentManager;
use Core\Factories\SchemaFactory;
use Core\Http\Response;
use Core\MetaManager;
use Core\SchemaHelper;
use Core\SiteConfig;
use Core\ViewRenderer;

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
    private SchemaFactory $schemaFactory;
    private SiteConfig $siteConfig;
    private ViewRenderer $viewRenderer;
    private ErrorController $errorController;

    public function __construct(
        ContentManager $contentManager,
        MetaManager $metaManager,
        SchemaHelper $schemaHelper,
        BlogRepository $blogRepository,
        SchemaFactory $schemaFactory,
        SiteConfig $siteConfig,
        ViewRenderer $viewRenderer,
        ErrorController $errorController
    ) {
        $this->contentManager = $contentManager;
        $this->metaManager = $metaManager;
        $this->schemaHelper = $schemaHelper;
        $this->blogRepository = $blogRepository;
        $this->schemaFactory = $schemaFactory;
        $this->siteConfig = $siteConfig;
        $this->viewRenderer = $viewRenderer;
        $this->errorController = $errorController;
    }

    public function index(): Response
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

        return Response::html($this->viewRenderer->render('pages/resources', [
            'active_page'  => 'resources.php',
            'all_posts'    => $all_posts,
            'posts_by_cat' => $posts_by_cat,
            'categories'   => $categories,
            'breadcrumbs'  => $breadcrumbs_schema,
        ]));
    }

    public function show(string $category, string $slug): Response
    {
        $slug = str_replace('.php', '', $slug);
        $path = "/blog/{$category}/{$slug}";

        $content = $this->contentManager->getParsedContent($path);

        if (!$content) {
            return $this->errorController->render404();
        }

        $post_metadata = $this->blogRepository->getPostBySlug($category, $slug);
        $all_posts = $this->blogRepository->getAllPosts();

        $page_config = $this->metaManager->buildFromMetadata($content['metadata'], $slug);

        $breadcrumbs = [
            'Home' => '/',
            'Resources' => '/resources',
            ucfirst($category) => "/resource/{$category}",
            $content['metadata']['title'] ?: ucfirst(str_replace('-', ' ', $slug)) => "/resource/{$category}/{$slug}"
        ];

        // Derive real dateModified from markdown file mtime via repository
        $dateModified = $this->blogRepository->getPostModifiedDate($category, $slug);
        $datePublished = $dateModified;

        if ($post_metadata && !empty($post_metadata['date'])) {
            $parsed = \DateTimeImmutable::createFromFormat('F Y', $post_metadata['date']);
            if ($parsed) {
                $datePublished = $parsed->format('Y-m-01');
            }
        }

        $page_config['additional_head'] = $this->schemaFactory->generateForPage(
            $category . '/' . $slug,
            'blog',
            $page_config,
            $datePublished,
            [],
            $breadcrumbs,
            $this->siteConfig->getUrl('/resource/' . $category . '/' . $slug)
        );

        return Response::html($this->viewRenderer->render('layouts/generic-post', [
            'content_html'     => $content['html'],
            'content_metadata' => $content['metadata'],
            'page_config'      => $page_config,
            'post_metadata'    => $post_metadata,
            'category'         => $category,
            'active_page'      => 'blog_post',
            'all_posts'        => $all_posts,
            'date_published'   => $datePublished,
            'date_modified'    => $dateModified,
        ]));
    }
}
