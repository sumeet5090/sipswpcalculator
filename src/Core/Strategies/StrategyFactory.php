<?php

declare(strict_types=1);

namespace Core\Strategies;

class StrategyFactory
{
    public static function create(string $slug): CalculatorStrategyInterface
    {
        if (strpos($slug, 'lumpsum') !== false) {
            return new LumpsumStrategy();
        }

        if (strpos($slug, 'crore') !== false) {
            return new TargetCorpusStrategy();
        }

        if (strpos($slug, 'retirement') !== false) {
            return new ComboStrategy();
        }

        if (strpos($slug, 'sip') !== false && strpos($slug, 'swp') === false) {
            return new SipStrategy();
        }

        if (strpos($slug, 'swp') !== false) {
            return new SwpStrategy();
        }

        // Default fallback to SIP
        return new SipStrategy();
    }
}
