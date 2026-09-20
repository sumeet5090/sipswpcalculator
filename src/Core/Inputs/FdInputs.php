<?php

declare(strict_types=1);

namespace Core\Inputs;

/**
 * Strongly-typed Input DTO for Fixed Deposit (FD) calculations.
 */
final class FdInputs implements CalculatorInputsInterface
{
    private float $amount;
    private float $rate;
    private int $years;
    private int $frequency;

    public function __construct(
        float $amount = 100000.0,
        float $rate = 7.0,
        int $years = 5,
        int $frequency = 4
    ) {
        $this->amount = max(0.0, $amount);
        $this->rate = max(0.0, $rate);
        $this->years = max(1, $years);
        $this->frequency = in_array($frequency, [1, 2, 4, 12], true) ? $frequency : 4;
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

    public function getFrequency(): int
    {
        return $this->frequency;
    }

    public function toTemplateData(): array
    {
        return [
            'fd_amount' => $this->amount,
            'fd_rate' => $this->rate,
            'fd_years' => $this->years,
            'fd_frequency' => $this->frequency,
        ];
    }
}
