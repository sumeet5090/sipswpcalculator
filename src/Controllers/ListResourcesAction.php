<?php

declare(strict_types=1);

namespace Controllers;

use Core\BlogRepository;
use Core\Http\Request;
use Core\Http\Response;
use Core\MetaManager;
use Core\SchemaHelper;
use Core\ViewRenderer;

/**
 * ListResourcesAction
 * Single Responsibility action dedicated to rendering the resources hub list.
 */
class ListResourcesAction
{
    private BlogRepository $blogRepository;
    private SchemaHelper $schemaHelper;
    private MetaManager $metaManager;
    private ViewRenderer $viewRenderer;

    public function __construct(
        BlogRepository $blogRepository,
        SchemaHelper $schemaHelper,
        MetaManager $metaManager,
        ViewRenderer $viewRenderer
    ) {
        $this->blogRepository = $blogRepository;
        $this->schemaHelper = $schemaHelper;
        $this->metaManager = $metaManager;
        $this->viewRenderer = $viewRenderer;
    }

    public function __invoke(?Request $request = null): Response
    {
        $allPosts = $this->blogRepository->getAllPosts();
        $categories = $this->blogRepository->getCategories();
        $postsByCat = $this->blogRepository->getPostsGroupedByCategory();

        $breadcrumbsSchema = $this->schemaHelper->getBreadcrumbs([
            'Home' => '/',
            'Resources' => '/resources'
        ]);

        $pageConfig = $this->metaManager->getMeta('resources');

        return Response::html($this->viewRenderer->render('pages/resources', [
            'page_config'  => $pageConfig,
            'active_page'  => 'resources',
            'all_posts'    => $allPosts,
            'posts_by_cat' => $postsByCat,
            'categories'   => $categories,
            'breadcrumbs'  => $breadcrumbsSchema,
        ]));
    }
}
