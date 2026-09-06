<?php

declare(strict_types=1);

namespace Controllers;

use Core\Http\Request;
use Core\Http\Response;
use Services\GuideRenderer;

class RenderEmbedAction
{
    private GuideRenderer $guideRenderer;

    public function __construct(GuideRenderer $guideRenderer)
    {
        $this->guideRenderer = $guideRenderer;
    }

    public function __invoke(Request $request, ?string $slug = null): Response
    {
        $uri = $request->getUri();
        // Remove /embed/ prefix
        $resolvedSlug = $slug ?? preg_replace('#^/embed/#', '', $uri);

        return $this->guideRenderer->renderEmbed((string) $resolvedSlug);
    }
}
