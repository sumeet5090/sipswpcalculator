<?php

declare(strict_types=1);

namespace Core\Strategies;

use Core\Inputs\CalculatorInputsInterface;
use Core\Inputs\IncomeTaxInputs;
use Core\Math\IncomeTaxEngine;

class IncomeTaxStrategy extends BaseStrategy
{
    public function getType(): string
    {
        return 'income_tax';
    }

    public function getInitialInputs(): CalculatorInputsInterface
    {
        $defaults = $this->configService->getCalculatorDefaults();
        $gross = (float) ($defaults['it_gross_salary']['default'] ?? IncomeTaxEngine::DEFAULT_GROSS_SALARY);
        $basic = (float) ($defaults['it_basic_salary']['default'] ?? IncomeTaxEngine::DEFAULT_BASIC_SALARY);
        $hraRec = (float) ($defaults['it_hra_received']['default'] ?? IncomeTaxEngine::DEFAULT_HRA_RECEIVED);
        $rent = (float) ($defaults['it_rent_paid']['default'] ?? IncomeTaxEngine::DEFAULT_RENT_PAID);
        $isMetro = (bool) ($defaults['it_is_metro']['default'] ?? IncomeTaxEngine::DEFAULT_IS_METRO);
        $sec80c = (float) ($defaults['it_sec_80c']['default'] ?? IncomeTaxEngine::DEFAULT_SEC_80C);
        $sec80d = (float) ($defaults['it_sec_80d']['default'] ?? IncomeTaxEngine::DEFAULT_SEC_80D);
        $nps = (float) ($defaults['it_nps_80ccd1b']['default'] ?? IncomeTaxEngine::DEFAULT_NPS_80CCD1B);
        $loan = (float) ($defaults['it_home_loan_24b']['default'] ?? IncomeTaxEngine::DEFAULT_HOME_LOAN_24B);

        return new IncomeTaxInputs(
            $gross,
            $basic,
            $hraRec,
            $rent,
            $isMetro,
            $sec80c,
            $sec80d,
            $nps,
            $loan
        );
    }

    public function getBenchmarkTemplate(): string
    {
        return 'components/benchmarks/income-tax-schedule.twig';
    }

    public function getBenchmarkTitle(): string
    {
        return 'FY 2025-26 Old vs. New Tax Regime Comparison Benchmark Table';
    }
}
