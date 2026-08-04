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

        // Look up categories and config
        $routesConfig = require __DIR__ . '/../Core/Config/routes.php';
        $calcConfig = $routesConfig['calculators']['/' . $slug] ?? null;

        if (!$calcConfig) {
            return ErrorController::handle404();
        }

        return $this->guideRenderer->render($slug);
    }
}
