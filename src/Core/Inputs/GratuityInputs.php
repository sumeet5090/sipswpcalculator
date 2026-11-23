<?php

declare(strict_types=1);

namespace Core\Inputs;

/**
 * Immutable Typed Input Value Object for the Statutory Indian Gratuity Calculator.
 */
final class GratuityInputs implements CalculatorInputsInterface
{
    private float $monthlySalary;
    private int $tenureYears;
    private int $tenureMonths;
    private bool $isCoveredByAct;

    public function __construct(
        float $monthlySalary,
        int $tenureYears,
        int $tenureMonths = 0,
        bool $isCoveredByAct = true
    ) {
        $this->monthlySalary = max(1000.0, min(10000000.0, $monthlySalary));
        $this->tenureYears = max(1, min(50, $tenureYears));
        $this->tenureMonths = max(0, min(11, $tenureMonths));
        $this->isCoveredByAct = $isCoveredByAct;
    }

    public function getMonthlySalary(): float
    {
        return $this->monthlySalary;
    }

    public function getTenureYears(): int
    {
        return $this->tenureYears;
    }

    public function getTenureMonths(): int
    {
        return $this->tenureMonths;
    }

    public function isCoveredByAct(): bool
    {
        return $this->isCoveredByAct;
    }

    /**
     * @return array<string, scalar>
     */
    public function toTemplateData(): array
    {
        return [
            'gratuity_salary'  => $this->monthlySalary,
            'gratuity_years'   => $this->tenureYears,
            'gratuity_months'  => $this->tenureMonths,
            'gratuity_covered' => $this->isCoveredByAct ? 1 : 0,
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
