<?php

declare(strict_types=1);

namespace Core\Inputs;

/**
 * Strongly-typed Input DTO for Future Value / Inflation calculations.
 */
final class InflationInputs implements CalculatorInputsInterface
{
    private float $amount;
    private float $rate;
    private int $years;

    public function __construct(
        float $amount = 50000.0,
        float $rate = 6.0,
        int $years = 10
    ) {
        $this->amount = max(0.0, $amount);
        $this->rate = max(0.0, $rate);
        $this->years = max(1, $years);
    }

    public function getAmount(): float
    {
        return $this->amount;
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
            'inf_amount' => $this->amount,
            'inf_rate' => $this->rate,
            'inf_years' => $this->years,
        ];
    }
}
