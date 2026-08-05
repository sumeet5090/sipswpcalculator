<?php

declare(strict_types=1);

namespace Controllers;

use Core\Http\Request;
use Core\Http\Response;
use Services\GuideRenderer;

class RenderGuideAction
{
    private GuideRenderer $guideRenderer;

    public function __construct(GuideRenderer $guideRenderer)
    {
        $this->guideRenderer = $guideRenderer;
    }

    public function __invoke(Request $request): Response
    {
        $uri = $request->getUri();
        $slug = ltrim($uri, '/');

        return $this->guideRenderer->render($slug);
    }
}
