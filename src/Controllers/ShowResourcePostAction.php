<?php

declare(strict_types=1);

namespace Controllers;

use Core\BlogRepository;
use Core\ContentManager;
use Core\Exceptions\ConfigurationException;
use Core\Exceptions\RouteNotFoundException;
use Core\Factories\SchemaFactory;
use Core\Http\Request;
use Core\Http\Response;
use Core\MetaManager;
use Core\SiteConfig;
use Core\ViewRenderer;

/**
 * ShowResourcePostAction
 * Single Responsibility action dedicated to parsing, building schemas, and rendering individual blog post articles.
 */
class ShowResourcePostAction
{
    private ContentManager $contentManager;
    private BlogRepository $blogRepository;
    private MetaManager $metaManager;
    private SchemaFactory $schemaFactory;
    private SiteConfig $siteConfig;
    private ViewRenderer $viewRenderer;

    public function __construct(
        ContentManager $contentManager,
        BlogRepository $blogRepository,
        MetaManager $metaManager,
        SchemaFactory $schemaFactory,
        SiteConfig $siteConfig,
        ViewRenderer $viewRenderer
    ) {
        $this->contentManager = $contentManager;
        $this->blogRepository = $blogRepository;
        $this->metaManager = $metaManager;
        $this->schemaFactory = $schemaFactory;
        $this->siteConfig = $siteConfig;
        $this->viewRenderer = $viewRenderer;
    }

    public function __invoke(string $category, string $slug, ?Request $request = null): Response
    {
        $cleanSlug = trim($slug, '/');
        $path = "/blog/{$category}/{$cleanSlug}";

        $content = $this->contentManager->getParsedContent($path);

        if (!$content) {
            throw new RouteNotFoundException("Blog post not found: {$category}/{$cleanSlug}");
        }

        $postMetadata = $this->blogRepository->getPostBySlug($category, $cleanSlug);
        $allPosts = $this->blogRepository->getAllPosts();

        $pageConfig = $this->metaManager->buildFromMetadata($content['metadata'], '/resource/' . $category . '/' . $cleanSlug);
        $pageConfig['og_type'] = 'article';

        if (empty($content['metadata']['title'])) {
            throw new ConfigurationException("Missing 'title' in frontmatter for blog post: {$category}/{$cleanSlug}");
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

        if ($postMetadata && !empty($postMetadata['date'])) {
            $datePublished = $this->blogRepository->formatPublishedDate((string) $postMetadata['date']);
        }

        $currentUri = "/resource/{$category}/{$cleanSlug}";

        $pageConfig['additional_head'] = $this->schemaFactory->generateForPage(
            $category . '/' . $cleanSlug,
            'blog',
            $pageConfig,
            $datePublished,
            [],
            $breadcrumbs,
            $this->siteConfig->getUrl($currentUri)
        );

        return Response::html($this->viewRenderer->render('layouts/generic-post', [
            'content_html'     => $content['html'],
            'content_metadata' => $content['metadata'],
            'page_config'      => $pageConfig,
            'post_metadata'    => $postMetadata,
            'seo_category'     => $category,
            'active_page'      => 'blog_post',
            'current_uri'      => $currentUri,
            'all_posts'        => $allPosts,
            'date_published'   => $datePublished,
            'date_modified'    => $dateModified,
        ]));
    }
}
