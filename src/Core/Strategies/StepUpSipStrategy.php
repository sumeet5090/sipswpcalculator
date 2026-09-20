<?php

declare(strict_types=1);

namespace Core\Strategies;

class StepUpSipStrategy extends SipStrategy
{
    public function getType(): string
    {
        return 'sip-step-up';
    }

    public function getBenchmarkTemplate(): string
    {
        return 'components/benchmarks/sip-stepup-comparison.twig';
    }

    public function getBenchmarkTitle(): string
    {
        return 'Step-Up SIP vs. Regular SIP Wealth Generation Comparison';
    }
}
