<?php

declare(strict_types=1);

namespace Controllers;

use Core\BlogRepository;
use Core\Exceptions\RouteNotFoundException;
use Core\Http\Request;
use Core\Http\Response;
use Core\MetaManager;
use Core\SchemaHelper;
use Core\SiteConfig;
use Core\ViewRenderer;

/**
 * ShowResourceCategoryAction
 * Single Responsibility action dedicated to rendering category-filtered resource lists.
 */
class ShowResourceCategoryAction
{
    private BlogRepository $blogRepository;
    private SchemaHelper $schemaHelper;
    private MetaManager $metaManager;
    private SiteConfig $siteConfig;
    private ViewRenderer $viewRenderer;

    public function __construct(
        BlogRepository $blogRepository,
        SchemaHelper $schemaHelper,
        MetaManager $metaManager,
        SiteConfig $siteConfig,
        ViewRenderer $viewRenderer
    ) {
        $this->blogRepository = $blogRepository;
        $this->schemaHelper = $schemaHelper;
        $this->metaManager = $metaManager;
        $this->siteConfig = $siteConfig;
        $this->viewRenderer = $viewRenderer;
    }

    public function __invoke(string $category, ?Request $request = null): Response
    {
        $allPosts = $this->blogRepository->getAllPosts();
        $categories = $this->blogRepository->getCategories();

        $filteredPosts = array_filter(
            $allPosts,
            fn($p) => strtolower((string) ($p['seo_category'] ?? '')) === strtolower($category)
        );

        if (empty($filteredPosts) && !in_array($category, $categories, true)) {
            throw new RouteNotFoundException("Resource category not found: {$category}");
        }

        $postsByCat = $this->blogRepository->getPostsGroupedByCategory();

        $breadcrumbsSchema = $this->schemaHelper->getBreadcrumbs([
            'Home' => '/',
            'Resources' => '/resources',
            ucfirst($category) => "/resource/{$category}"
        ]);

        $catMeta = $categories[$category] ?? [];
        $catTitle = !empty($catMeta['title']) ? $catMeta['title'] : ucfirst($category);
        $pageConfig = $this->metaManager->setDynamicMeta(
            "{$catTitle} Resources — SIP & SWP Calculator",
            "Expert financial guides and calculators for {$catTitle}.",
            $this->siteConfig->getUrl("/resource/{$category}")
        );

        return Response::html($this->viewRenderer->render('pages/resources', [
            'page_config'       => $pageConfig,
            'active_page'       => 'resources',
            'all_posts'         => !empty($filteredPosts) ? array_values($filteredPosts) : $allPosts,
            'posts_by_cat'      => $postsByCat,
            'categories'        => $categories,
            'selected_category' => $category,
            'breadcrumbs'       => $breadcrumbsSchema,
        ]));
    }
}
