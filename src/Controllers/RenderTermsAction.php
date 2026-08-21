<?php

declare(strict_types=1);

namespace Controllers;

use Core\Http\Request;
use Core\Http\Response;
use Core\MetaManager;
use Core\SchemaHelper;
use Core\ViewRenderer;

/**
 * RenderTermsAction
 * Single Responsibility action dedicated strictly to rendering the Terms of Service page.
 */
class RenderTermsAction
{
    private SchemaHelper $schemaHelper;
    private ViewRenderer $viewRenderer;
    private MetaManager $metaManager;

    public function __construct(
        SchemaHelper $schemaHelper,
        ViewRenderer $viewRenderer,
        MetaManager $metaManager
    ) {
        $this->schemaHelper = $schemaHelper;
        $this->viewRenderer = $viewRenderer;
        $this->metaManager = $metaManager;
    }

    public function __invoke(?Request $request = null): Response
    {
        $page_config = $this->metaManager->getMeta('terms');

        $breadcrumbs = $this->schemaHelper->getBreadcrumbs([
            'Home' => '/',
            'Terms of Service' => '/terms'
        ]);

        return Response::html($this->viewRenderer->render('pages/terms', [
            'breadcrumbs' => $breadcrumbs,
            'page_config' => $page_config,
            'active_page' => 'terms',
        ]));
    }
}
