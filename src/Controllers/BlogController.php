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

    public function __construct(
        ContentManager $contentManager,
        MetaManager $metaManager,
        SchemaHelper $schemaHelper,
        BlogRepository $blogRepository,
        SchemaFactory $schemaFactory,
        SiteConfig $siteConfig,
        ViewRenderer $viewRenderer
    ) {
        $this->contentManager = $contentManager;
        $this->metaManager = $metaManager;
        $this->schemaHelper = $schemaHelper;
        $this->blogRepository = $blogRepository;
        $this->schemaFactory = $schemaFactory;
        $this->siteConfig = $siteConfig;
        $this->viewRenderer = $viewRenderer;
    }

    public function index(): Response
    {
        $all_posts = $this->blogRepository->getAllPosts();
        $categories = $this->blogRepository->getCategories();

        $posts_by_cat = [];
        foreach ($all_posts as $post) {
            $posts_by_cat[$post['seo_category']][] = $post;
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
        $cleanSlug = basename($slug, '.php');
        $path = "/blog/{$category}/{$cleanSlug}";

        $content = $this->contentManager->getParsedContent($path);

        if (!$content) {
            throw new \Core\Exceptions\RouteNotFoundException("Blog post not found: {$category}/{$cleanSlug}");
        }

        $post_metadata = $this->blogRepository->getPostBySlug($category, $cleanSlug);
        $all_posts = $this->blogRepository->getAllPosts();

        $page_config = $this->metaManager->buildFromMetadata($content['metadata'], $cleanSlug);

        if (empty($content['metadata']['title'])) {
            throw new \RuntimeException("Missing 'title' in frontmatter for blog post: {$category}/{$cleanSlug}");
        }
        $breadcrumbTitle = (string) $content['metadata']['title'];

        $breadcrumbs = [
            'Home' => '/',
            'Resources' => '/resources',
            ucfirst($category) => "/resource/{$category}",
            $breadcrumbTitle => "/resource/{$category}/{$cleanSlug}"
        ];

        // Derive real dateModified from markdown file mtime via repository
        $dateModified = $this->blogRepository->getPostModifiedDate($category, $cleanSlug);
        $datePublished = $dateModified;

        if ($post_metadata && !empty($post_metadata['date'])) {
            $parsed = \DateTimeImmutable::createFromFormat('F Y', $post_metadata['date']);
            if ($parsed) {
                $datePublished = $parsed->format('Y-m-01');
            }
        }

        $page_config['additional_head'] = $this->schemaFactory->generateForPage(
            $category . '/' . $cleanSlug,
            'blog',
            $page_config,
            $datePublished,
            [],
            $breadcrumbs,
            $this->siteConfig->getUrl('/resource/' . $category . '/' . $cleanSlug)
        );

        return Response::html($this->viewRenderer->render('layouts/generic-post', [
            'content_html'     => $content['html'],
            'content_metadata' => $content['metadata'],
            'page_config'      => $page_config,
            'post_metadata'    => $post_metadata,
            'seo_category'     => $category,
            'active_page'      => 'blog_post',
            'all_posts'        => $all_posts,
            'date_published'   => $datePublished,
            'date_modified'    => $dateModified,
        ]));
    }
}
