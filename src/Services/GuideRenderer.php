<?php

declare(strict_types=1);

namespace Services;

use Core\Http\Response;
use Core\ViewRenderer;

/**
 * GuideRenderer
 * Renders educational guides and calculator pages using GuideViewModelBuilder.
 */
class GuideRenderer
{
    private GuideViewModelBuilder $viewModelBuilder;
    private ViewRenderer $viewRenderer;

    public function __construct(
        GuideViewModelBuilder $viewModelBuilder,
        ViewRenderer $viewRenderer
    ) {
        $this->viewModelBuilder = $viewModelBuilder;
        $this->viewRenderer = $viewRenderer;
    }

    /**
     * Parse, build view model, and render an educational guide template.
     *
     * @param string $slug Guide URL path slug (e.g. 'sip-calculator')
     */
    public function render(string $slug): Response
    {
        $viewModel = $this->viewModelBuilder->build($slug);

        return Response::html($this->viewRenderer->render($viewModel['layout'], $viewModel['data']));
    }
}
