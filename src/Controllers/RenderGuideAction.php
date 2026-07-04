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

    public function __invoke(Request $request): void
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $slug = ltrim($uri, '/');

        // Look up categories and config
        $routesConfig = require __DIR__ . '/../Core/Config/routes.php';
        $calcConfig = $routesConfig['calculators']['/' . $slug] ?? null;

        if (!$calcConfig) {
            http_response_code(404);
            echo "404 Calculator Route Not Found";
            return;
        }

        $this->guideRenderer->render($slug, $calcConfig['category'], $calcConfig['date']);
    }
}
