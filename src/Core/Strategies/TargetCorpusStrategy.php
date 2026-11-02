<?php

declare(strict_types=1);

namespace Core\Strategies;

use Services\ConfigServiceInterface;

class TargetCorpusStrategy extends BaseStrategy
{
    private float $defaultTargetCorpus;

    public function __construct(ConfigServiceInterface $configService, float $defaultTargetCorpus = 10000000.0)
    {
        parent::__construct($configService);
        $this->defaultTargetCorpus = $defaultTargetCorpus;
    }

    public function getTargetCorpus(): float
    {
        return $this->defaultTargetCorpus;
    }

    public function getType(): string
    {
        return 'target_corpus';
    }

    public function getInitialInputs(): \Core\Inputs\CalculatorInputsInterface
    {
        $cfg = $this->configService->getCalculatorDefaults();
        $exemption = (float) ($cfg['ltcg_tax']['exemption_threshold'] ?? 125000.0);
        $taxRate = (float) ($cfg['ltcg_tax']['rate'] ?? 0.125);

        return \Core\InvestmentInputs::fromValues(
            0.0,
            15,
            12.0,
            10.0,
            false,
            0.0,
            0.0,
            0,
            0.0,
            0.0,
            0.0,
            $exemption,
            $taxRate
        );
    }

    public function getBenchmarkTemplate(): string
    {
        return 'components/benchmarks/target-corpus.twig';
    }

    public function getBenchmarkTitle(): string
    {
        return 'Target Corpus Goal-Seek Matrix: Required Monthly SIP (12% CAGR)';
    }
}
