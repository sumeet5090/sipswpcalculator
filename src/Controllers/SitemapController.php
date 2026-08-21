<?php

declare(strict_types=1);

namespace Controllers;

use Core\Http\Response;
use Core\ViewRenderer;
use Services\SitemapGenerator;

class SitemapController
{
    private SitemapGenerator $sitemapGenerator;
    private ViewRenderer $viewRenderer;

    public function __construct(
        SitemapGenerator $sitemapGenerator,
        ViewRenderer $viewRenderer
    ) {
        $this->sitemapGenerator = $sitemapGenerator;
        $this->viewRenderer = $viewRenderer;
    }

    public function index(): Response
    {
        $urls = $this->sitemapGenerator->generateUrlNodes();
        $xml = $this->viewRenderer->render('sitemap.xml', ['urls' => $urls]);

        return new Response($xml, 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }
}
