<?php

declare(strict_types=1);

namespace Core\Inputs;

/**
 * Immutable Typed Input Value Object for Post-Retirement Asset Allocation Calculator.
 */
final class PostRetirementInputs implements CalculatorInputsInterface
{
    private float $existingCorpus;
    private float $gratuityLumpSum;
    private float $npsLumpSum;
    private float $guaranteedPension;
    private float $monthlyExpense;
    private float $expectedReturn;
    private float $inflationRate;
    private int $currentAge;
    private int $planningHorizonYears;

    public function __construct(
        float $existingCorpus,
        float $gratuityLumpSum,
        float $npsLumpSum,
        float $guaranteedPension,
        float $monthlyExpense,
        float $expectedReturn,
        float $inflationRate = 6.0,
        int $currentAge = 60,
        int $planningHorizonYears = 30
    ) {
        $this->existingCorpus = max(0.0, min(500000000.0, $existingCorpus));
        $this->gratuityLumpSum = max(0.0, min(50000000.0, $gratuityLumpSum));
        $this->npsLumpSum = max(0.0, min(100000000.0, $npsLumpSum));
        $this->guaranteedPension = max(0.0, min(500000.0, $guaranteedPension));
        $this->monthlyExpense = max(10000.0, min(1000000.0, $monthlyExpense));
        $this->expectedReturn = max(4.0, min(14.0, $expectedReturn));
        $this->inflationRate = max(3.0, min(10.0, $inflationRate));
        $this->currentAge = max(40, min(80, $currentAge));
        $this->planningHorizonYears = max(10, min(45, $planningHorizonYears));
    }

    public function getExistingCorpus(): float
    {
        return $this->existingCorpus;
    }

    public function getGratuityLumpSum(): float
    {
        return $this->gratuityLumpSum;
    }

    public function getNpsLumpSum(): float
    {
        return $this->npsLumpSum;
    }

    public function getGuaranteedPension(): float
    {
        return $this->guaranteedPension;
    }

    public function getMonthlyExpense(): float
    {
        return $this->monthlyExpense;
    }

    public function getExpectedReturn(): float
    {
        return $this->expectedReturn;
    }

    public function getInflationRate(): float
    {
        return $this->inflationRate;
    }

    public function getCurrentAge(): int
    {
        return $this->currentAge;
    }

    public function getPlanningHorizonYears(): int
    {
        return $this->planningHorizonYears;
    }

    /**
     * @return array<string, scalar>
     */
    public function toTemplateData(): array
    {
        return [
            'post_ret_existing_corpus'  => $this->existingCorpus,
            'post_ret_gratuity'         => $this->gratuityLumpSum,
            'post_ret_nps_lump_sum'     => $this->npsLumpSum,
            'post_ret_pension'          => $this->guaranteedPension,
            'post_ret_expense'          => $this->monthlyExpense,
            'post_ret_return'           => $this->expectedReturn,
            'post_ret_inflation'        => $this->inflationRate,
            'post_ret_age'              => $this->currentAge,
            'post_ret_horizon'          => $this->planningHorizonYears,
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
