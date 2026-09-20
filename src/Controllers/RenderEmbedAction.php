<?php

declare(strict_types=1);

namespace Controllers;

use Core\Http\Request;
use Core\Http\Response;
use Services\GuideRendererInterface;

class RenderEmbedAction
{
    private GuideRendererInterface $guideRenderer;

    public function __construct(GuideRendererInterface $guideRenderer)
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
