<?php

declare(strict_types=1);

namespace Core\Inputs;

/**
 * Strongly-typed Input DTO for Compound Annual Growth Rate (CAGR) calculations.
 */
final class CagrInputs implements CalculatorInputsInterface
{
    private float $initialValue;
    private float $finalValue;
    private float $years;

    public function __construct(
        float $initialValue = 100000.0,
        float $finalValue = 250000.0,
        float $years = 5.0
    ) {
        $this->initialValue = max(0.0, $initialValue);
        $this->finalValue = max(0.0, $finalValue);
        $this->years = max(0.1, $years);
    }

    public function getInitialValue(): float
    {
        return $this->initialValue;
    }

    public function getFinalValue(): float
    {
        return $this->finalValue;
    }

    public function getYears(): float
    {
        return $this->years;
    }

    public function toTemplateData(): array
    {
        return [
            'cagr_initial' => $this->initialValue,
            'cagr_final' => $this->finalValue,
            'cagr_years' => $this->years,
        ];
    }
}
