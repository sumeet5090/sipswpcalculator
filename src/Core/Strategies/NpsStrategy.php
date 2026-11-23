<?php

declare(strict_types=1);

namespace Core\Strategies;

use Core\Inputs\CalculatorInputsInterface;
use Core\Inputs\NpsInputs;

class NpsStrategy extends BaseStrategy
{
    public function getType(): string
    {
        return 'nps';
    }

    public function getInitialInputs(): CalculatorInputsInterface
    {
        $defaults = $this->configService->getCalculatorDefaults();
        $contribution = (float) ($defaults['nps_monthly_contribution']['default'] ?? 5000.0);
        $currentAge = (int) ($defaults['nps_current_age']['default'] ?? 30);
        $retirementAge = (int) ($defaults['nps_retirement_age']['default'] ?? 60);
        $expectedReturn = (float) ($defaults['nps_expected_return']['default'] ?? 10.0);
        $annuityPercent = (float) ($defaults['nps_annuity_percent']['default'] ?? 40.0);
        $annuityRate = (float) ($defaults['nps_annuity_rate']['default'] ?? 6.0);

        return new NpsInputs(
            $contribution,
            $currentAge,
            $retirementAge,
            $expectedReturn,
            $annuityPercent,
            $annuityRate
        );
    }

    public function getBenchmarkTemplate(): string
    {
        return 'components/benchmarks/nps-schedule.twig';
    }

    public function getBenchmarkTitle(): string
    {
        return 'National Pension System (NPS) Wealth & Pension Benchmark Reference Table';
    }
}
