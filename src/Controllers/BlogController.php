<?php

declare(strict_types=1);

namespace Controllers;

use Core\Http\Request;
use Core\Http\Response;

/**
 * BlogController
 * Composite facade delegating resource actions to dedicated single-responsibility controllers.
 */
class BlogController
{
    private ListResourcesAction $listAction;
    private ShowResourceCategoryAction $categoryAction;
    private ShowResourcePostAction $postAction;

    public function __construct(
        ListResourcesAction $listAction,
        ShowResourceCategoryAction $categoryAction,
        ShowResourcePostAction $postAction
    ) {
        $this->listAction = $listAction;
        $this->categoryAction = $categoryAction;
        $this->postAction = $postAction;
    }

    public function index(?Request $request = null): Response
    {
        return ($this->listAction)($request);
    }

    public function category(string $category, ?Request $request = null): Response
    {
        return ($this->categoryAction)($category, $request);
    }

    public function show(string $category, string $slug, ?Request $request = null): Response
    {
        return ($this->postAction)($category, $slug, $request);
    }
}
