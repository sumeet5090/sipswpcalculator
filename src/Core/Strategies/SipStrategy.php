<?php

declare(strict_types=1);

namespace Core\Strategies;

use Core\Inputs\CalculatorInputsInterface;
use Core\InvestmentInputs;
use Services\ConfigServiceInterface;

class SipStrategy extends BaseStrategy
{
    private ?float $defaultSip;

    public function __construct(ConfigServiceInterface $configService, ?float $defaultSip = null)
    {
        parent::__construct($configService);
        $this->defaultSip = $defaultSip;
    }

    public function getType(): string
    {
        return 'sip';
    }

    public function getInitialInputs(): CalculatorInputsInterface
    {
        $overrides = $this->defaultSip !== null ? ['sip' => $this->defaultSip] : [];
        return InvestmentInputs::fromRequest($overrides, $this->configService);
    }
}
