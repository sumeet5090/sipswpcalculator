<?php

declare(strict_types=1);

namespace Core\Strategies;

use Services\ConfigService;

class StrategyFactory
{
    private ConfigService $configService;

    public function __construct(ConfigService $configService)
    {
        $this->configService = $configService;
    }

    public function create(string $slug): CalculatorStrategyInterface
    {
        if (strpos($slug, 'lumpsum') !== false) {
            return new LumpsumStrategy($this->configService);
        }

        if (strpos($slug, 'crore') !== false) {
            return new TargetCorpusStrategy($this->configService);
        }

        if (strpos($slug, 'retirement') !== false) {
            return new ComboStrategy($this->configService);
        }

        if (strpos($slug, 'sip') !== false && strpos($slug, 'swp') === false) {
            return new SipStrategy($this->configService);
        }

        if (strpos($slug, 'swp') !== false) {
            return new SwpStrategy($this->configService);
        }

        // Default fallback to SIP
        return new SipStrategy($this->configService);
    }
}
