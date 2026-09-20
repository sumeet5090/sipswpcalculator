<?php

declare(strict_types=1);

namespace Core\Inputs;

/**
 * Strongly-typed Input DTO for Equated Monthly Installment (EMI) calculations.
 */
final class EmiInputs implements CalculatorInputsInterface
{
    private float $principal;
    private float $rate;
    private int $years;

    public function __construct(
        float $principal = 3000000.0,
        float $rate = 8.5,
        int $years = 20
    ) {
        $this->principal = max(0.0, $principal);
        $this->rate = max(0.0, $rate);
        $this->years = max(1, $years);
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

    public function toTemplateData(): array
    {
        return [
            'emi_principal' => $this->principal,
            'emi_rate' => $this->rate,
            'emi_years' => $this->years,
        ];
    }
}
