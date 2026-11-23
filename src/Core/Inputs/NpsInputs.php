<?php

declare(strict_types=1);

namespace Core\Inputs;

/**
 * Immutable Typed Input Value Object for National Pension System (NPS) Calculator.
 */
final class NpsInputs implements CalculatorInputsInterface
{
    private float $monthlyContribution;
    private int $currentAge;
    private int $retirementAge;
    private float $expectedReturnRate;
    private float $annuityPercent;
    private float $annuityReturnRate;

    public function __construct(
        float $monthlyContribution,
        int $currentAge,
        int $retirementAge,
        float $expectedReturnRate,
        float $annuityPercent = 40.0,
        float $annuityReturnRate = 6.0
    ) {
        $this->monthlyContribution = max(500.0, min(150000.0, $monthlyContribution));
        $this->currentAge = max(18, min(65, $currentAge));
        $this->retirementAge = max($this->currentAge + 1, min(75, $retirementAge));
        $this->expectedReturnRate = max(5.0, min(15.0, $expectedReturnRate));
        $this->annuityPercent = max(40.0, min(100.0, $annuityPercent));
        $this->annuityReturnRate = max(4.0, min(10.0, $annuityReturnRate));
    }

    public function getMonthlyContribution(): float
    {
        return $this->monthlyContribution;
    }

    public function getCurrentAge(): int
    {
        return $this->currentAge;
    }

    public function getRetirementAge(): int
    {
        return $this->retirementAge;
    }

    public function getExpectedReturnRate(): float
    {
        return $this->expectedReturnRate;
    }

    public function getAnnuityPercent(): float
    {
        return $this->annuityPercent;
    }

    public function getAnnuityReturnRate(): float
    {
        return $this->annuityReturnRate;
    }

    /**
     * @return array<string, scalar>
     */
    public function toTemplateData(): array
    {
        return [
            'nps_monthly_contribution' => $this->monthlyContribution,
            'nps_current_age'          => $this->currentAge,
            'nps_retirement_age'       => $this->retirementAge,
            'nps_expected_return'      => $this->expectedReturnRate,
            'nps_annuity_percent'      => $this->annuityPercent,
            'nps_annuity_rate'         => $this->annuityReturnRate,
        ];
    }

    /**
     * @return array<string, scalar>
     */
    public function toArray(): array
    {
        return $this->toTemplateData();
    }
}
