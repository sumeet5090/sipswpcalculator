<?php

declare(strict_types=1);

namespace Services;

use Core\Http\Response;

/**
 * GuideRendererInterface
 * Contract for rendering educational guide pages and embeddable calculator widgets.
 */
interface GuideRendererInterface
{
    /**
     * Parse, build view model, and render an educational guide template.
     *
     * @param string $slug Guide URL path slug (e.g. 'sip-calculator')
     */
    public function render(string $slug): Response;

    /**
     * Render a lightweight embeddable calculator widget template for iframes.
     */
    public function renderEmbed(string $slug): Response;
}
