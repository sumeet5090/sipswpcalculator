<?php

declare(strict_types=1);

namespace Core\Inputs;

/**
 * CalculatorInputsInterface
 * Base contract for strongly-typed calculator input parameters.
 * Provides polymorphic conversion to template presentation arrays.
 */
interface CalculatorInputsInterface
{
    /**
     * Export input parameters as an associative array for view templates.
     *
     * @return array<string, mixed>
     */
    public function toTemplateData(): array;
}
