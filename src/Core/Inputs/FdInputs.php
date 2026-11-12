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
    private float $years;
    private int $frequency;
    private bool $isSenior;
    private string $payoutFrequency;

    public function __construct(
        float $amount = 500000.0,
        float $rate = 7.0,
        float|int $years = 3.0,
        int $frequency = 4,
        bool $isSenior = false,
        string $payoutFrequency = 'cumulative'
    ) {
        $this->amount = max(0.0, $amount);
        $this->rate = max(0.0, $rate);
        $this->years = max(0.25, (float) $years);
        $this->frequency = in_array($frequency, [1, 2, 4, 12], true) ? $frequency : 4;
        $this->isSenior = $isSenior;
        $this->payoutFrequency = in_array($payoutFrequency, ['cumulative', 'monthly', 'quarterly', 'annual'], true)
            ? $payoutFrequency
            : 'cumulative';
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getRate(): float
    {
        return $this->rate;
    }

    public function getYears(): float
    {
        return $this->years;
    }

    public function getFrequency(): int
    {
        return $this->frequency;
    }

    public function isSenior(): bool
    {
        return $this->isSenior;
    }

    public function getPayoutFrequency(): string
    {
        return $this->payoutFrequency;
    }

    public function toTemplateData(): array
    {
        return [
            'fd_amount' => $this->amount,
            'fd_principal' => $this->amount,
            'fd_rate' => $this->rate,
            'fd_years' => $this->years,
            'fd_senior' => $this->isSenior,
            'fd_frequency' => $this->payoutFrequency,
            'fd_compounding_frequency' => $this->frequency,
        ];
    }
}
