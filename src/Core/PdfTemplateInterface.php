<?php

declare(strict_types=1);

namespace Core;

/**
 * PdfTemplateInterface
 * Abstraction contract for rendering PDF HTML report templates.
 */
interface PdfTemplateInterface
{
    /**
     * Render calculation inputs and results into an HTML document template.
     *
     * @param array<string, mixed> $inputs
     * @return string
     */
    public function render(array $inputs): string;
}
