<?php

declare(strict_types=1);

namespace Core\Inputs;

use Core\Math\IncomeTaxEngine;

/**
 * Immutable Typed Input Value Object for Old vs. New Income Tax & HRA Calculator.
 */
final class IncomeTaxInputs implements CalculatorInputsInterface
{
    private float $grossSalary;
    private float $basicSalary;
    private float $hraReceived;
    private float $rentPaid;
    private bool $isMetro;
    private float $sec80c;
    private float $sec80d;
    private float $nps80ccd1b;
    private float $homeLoan24b;

    public function __construct(
        float $grossSalary,
        float $basicSalary,
        float $hraReceived = IncomeTaxEngine::DEFAULT_HRA_RECEIVED,
        float $rentPaid = IncomeTaxEngine::DEFAULT_RENT_PAID,
        bool $isMetro = IncomeTaxEngine::DEFAULT_IS_METRO,
        float $sec80c = IncomeTaxEngine::DEFAULT_SEC_80C,
        float $sec80d = IncomeTaxEngine::DEFAULT_SEC_80D,
        float $nps80ccd1b = IncomeTaxEngine::DEFAULT_NPS_80CCD1B,
        float $homeLoan24b = IncomeTaxEngine::DEFAULT_HOME_LOAN_24B
    ) {
        $this->grossSalary = max(IncomeTaxEngine::MIN_GROSS_SALARY, min(IncomeTaxEngine::MAX_GROSS_SALARY, $grossSalary));
        $this->basicSalary = max(IncomeTaxEngine::MIN_BASIC_SALARY, min($this->grossSalary, min(IncomeTaxEngine::MAX_BASIC_SALARY, $basicSalary)));
        $this->hraReceived = max(0.0, min($this->grossSalary, min(IncomeTaxEngine::MAX_HRA_RENT, $hraReceived)));
        $this->rentPaid = max(0.0, min(IncomeTaxEngine::MAX_HRA_RENT, $rentPaid));
        $this->isMetro = $isMetro;
        $this->sec80c = max(0.0, min(IncomeTaxEngine::MAX_SEC_80C, $sec80c));
        $this->sec80d = max(0.0, min(IncomeTaxEngine::MAX_SEC_80D, $sec80d));
        $this->nps80ccd1b = max(0.0, min(IncomeTaxEngine::MAX_NPS_80CCD1B, $nps80ccd1b));
        $this->homeLoan24b = max(0.0, min(IncomeTaxEngine::MAX_HOME_LOAN_24B, $homeLoan24b));
    }

    public function getGrossSalary(): float
    {
        return $this->grossSalary;
    }

    public function getBasicSalary(): float
    {
        return $this->basicSalary;
    }

    public function getHraReceived(): float
    {
        return $this->hraReceived;
    }

    public function getRentPaid(): float
    {
        return $this->rentPaid;
    }

    public function isMetro(): bool
    {
        return $this->isMetro;
    }

    public function getSec80c(): float
    {
        return $this->sec80c;
    }

    public function getSec80d(): float
    {
        return $this->sec80d;
    }

    public function getNps80ccd1b(): float
    {
        return $this->nps80ccd1b;
    }

    public function getHomeLoan24b(): float
    {
        return $this->homeLoan24b;
    }

    /**
     * @return array<string, mixed>
     */
    public function toTemplateData(): array
    {
        return [
            'it_gross_salary' => $this->grossSalary,
            'it_basic_salary' => $this->basicSalary,
            'it_hra_received' => $this->hraReceived,
            'it_rent_paid' => $this->rentPaid,
            'it_is_metro' => $this->isMetro ? 1 : 0,
            'it_sec_80c' => $this->sec80c,
            'it_sec_80d' => $this->sec80d,
            'it_nps_80ccd1b' => $this->nps80ccd1b,
            'it_home_loan_24b' => $this->homeLoan24b,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->toTemplateData();
    }
}
