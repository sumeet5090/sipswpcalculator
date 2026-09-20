<?php

declare(strict_types=1);

namespace Core\Inputs;

/**
 * Strongly-typed Input DTO for Compound Interest calculations.
 */
final class CompoundInterestInputs implements CalculatorInputsInterface
{
    private float $principal;
    private float $rate;
    private int $years;
    private int $frequency;

    public function __construct(
        float $principal = 500000.0,
        float $rate = 12.0,
        int $years = 10,
        int $frequency = 12
    ) {
        $this->principal = max(0.0, $principal);
        $this->rate = max(0.0, $rate);
        $this->years = max(1, $years);
        $this->frequency = in_array($frequency, [1, 2, 4, 12], true) ? $frequency : 12;
    }

    public function getPrincipal(): float
    {
        return $this->principal;
    }

    public function getRate(): float
    {
        return $this->rate;
    }

    public function getYears(): int
    {
        return $this->years;
    }

    public function getFrequency(): int
    {
        return $this->frequency;
    }

    public function toTemplateData(): array
    {
        return [
            'ci_principal' => $this->principal,
            'ci_rate' => $this->rate,
            'ci_years' => $this->years,
            'ci_frequency' => $this->frequency,
        ];
    }
}
