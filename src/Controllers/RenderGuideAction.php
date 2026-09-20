<?php

declare(strict_types=1);

namespace Controllers;

use Core\Http\Request;
use Core\Http\Response;
use Services\GuideRendererInterface;

class RenderGuideAction
{
    private GuideRendererInterface $guideRenderer;

    public function __construct(GuideRendererInterface $guideRenderer)
    {
        $this->guideRenderer = $guideRenderer;
    }

    public function __invoke(Request $request, ?string $slug = null): Response
    {
        $resolvedSlug = $slug ?? ltrim($request->getUri(), '/');

        return $this->guideRenderer->render($resolvedSlug);
    }
}
