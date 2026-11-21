<?php

declare(strict_types=1);

namespace Core\Strategies;

use Core\InvestmentInputs;
use Services\ConfigServiceInterface;

class SwpStrategy extends BaseStrategy
{
    private ?float $defaultCorpus;
    private ?float $defaultWithdrawal;

    public function __construct(
        ConfigServiceInterface $configService,
        ?float $defaultCorpus = null,
        ?float $defaultWithdrawal = null
    ) {
        parent::__construct($configService);
        $this->defaultCorpus = $defaultCorpus;
        $this->defaultWithdrawal = $defaultWithdrawal;
    }

    public function getDefaultCorpus(): ?float
    {
        return $this->defaultCorpus;
    }

    public function getDefaultWithdrawal(): ?float
    {
        return $this->defaultWithdrawal;
    }

    public function getType(): string
    {
        return 'swp';
    }

    public function getInitialInputs(): InvestmentInputs
    {
        $overrides = [];
        if ($this->defaultCorpus !== null) {
            $overrides['corpus'] = $this->defaultCorpus;
        }
        if ($this->defaultWithdrawal !== null) {
            $overrides['swp_withdrawal'] = $this->defaultWithdrawal;
        }

        return InvestmentInputs::fromSwpRequest($overrides, $this->configService);
    }

    public function getBenchmarkTemplate(): string
    {
        return 'components/benchmarks/swp-longevity.twig';
    }

    public function getBenchmarkTitle(): string
    {
        return 'SWP Withdrawal & Corpus Longevity Benchmark Table';
    }
}
