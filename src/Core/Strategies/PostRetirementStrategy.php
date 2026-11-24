<?php

declare(strict_types=1);

namespace Core\Strategies;

use Core\Inputs\CalculatorInputsInterface;
use Core\Inputs\PostRetirementInputs;

class PostRetirementStrategy extends BaseStrategy
{
    public function getType(): string
    {
        return 'post_retirement';
    }

    public function getInitialInputs(): CalculatorInputsInterface
    {
        $defaults = $this->configService->getCalculatorDefaults();
        $existing = (float) ($defaults['post_ret_existing_corpus']['default'] ?? 5000000.0);
        $gratuity = (float) ($defaults['post_ret_gratuity']['default'] ?? 1500000.0);
        $nps = (float) ($defaults['post_ret_nps_lump_sum']['default'] ?? 4000000.0);
        $pension = (float) ($defaults['post_ret_pension']['default'] ?? 25000.0);
        $expense = (float) ($defaults['post_ret_expense']['default'] ?? 75000.0);
        $return = (float) ($defaults['post_ret_return']['default'] ?? 8.5);
        $inflation = (float) ($defaults['post_ret_inflation']['default'] ?? 6.0);
        $age = (int) ($defaults['post_ret_age']['default'] ?? 60);
        $horizon = (int) ($defaults['post_ret_horizon']['default'] ?? 30);

        return new PostRetirementInputs(
            $existing,
            $gratuity,
            $nps,
            $pension,
            $expense,
            $return,
            $inflation,
            $age,
            $horizon
        );
    }

    public function getBenchmarkTemplate(): string
    {
        return 'components/benchmarks/post-retirement-schedule.twig';
    }

    public function getBenchmarkTitle(): string
    {
        return 'Post-Retirement Multi-Stream Asset Drawdown & Longevity Reference Table';
    }
}
