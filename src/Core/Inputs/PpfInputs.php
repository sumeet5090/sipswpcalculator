<?php

declare(strict_types=1);

namespace Core\Inputs;

/**
 * Strongly-typed Input DTO for Public Provident Fund (PPF) calculations.
 */
final class PpfInputs implements CalculatorInputsInterface
{
    private float $annualInvestment;
    private float $rate;
    private int $years;
    private string $timing;

    public function __construct(
        float $annualInvestment = 150000.0,
        float $rate = 7.1,
        int $years = 15,
        string $timing = 'beginning'
    ) {
        $this->annualInvestment = max(500.0, min(150000.0, $annualInvestment));
        $this->rate = max(0.0, $rate);
        $this->years = max(15, $years);
        $this->timing = ($timing === 'monthly') ? 'monthly' : 'beginning';
    }

    public function getAnnualInvestment(): float
    {
        return $this->annualInvestment;
    }

    public function getRate(): float
    {
        return $this->rate;
    }

    public function getYears(): int
    {
        return $this->years;
    }

    public function getTiming(): string
    {
        return $this->timing;
    }

    public function toTemplateData(): array
    {
        return [
            'ppf_annual_investment' => $this->annualInvestment,
            'ppf_deposit' => $this->annualInvestment,
            'ppf_rate' => $this->rate,
            'ppf_years' => $this->years,
            'ppf_timing' => $this->timing,
        ];
    }
}
