<?php

declare(strict_types=1);

namespace Controllers;

use Core\Http\Request;
use Core\Http\Response;
use Core\MetaManager;
use Core\ViewRenderer;

/**
 * RenderAboutAction
 * Single Responsibility action dedicated strictly to rendering the About page.
 */
class RenderAboutAction
{
    private MetaManager $metaManager;
    private ViewRenderer $viewRenderer;

    public function __construct(MetaManager $metaManager, ViewRenderer $viewRenderer)
    {
        $this->metaManager = $metaManager;
        $this->viewRenderer = $viewRenderer;
    }

    public function __invoke(?Request $request = null): Response
    {
        $page_config = $this->metaManager->getMeta('about');

        return Response::html($this->viewRenderer->render('pages/about', [
            'page_config' => $page_config,
            'active_page' => 'about',
        ]));
    }
}
