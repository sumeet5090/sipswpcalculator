<?php

declare(strict_types=1);

namespace Core\Strategies;

use Core\InvestmentInputs;

interface CalculatorStrategyInterface
{
    /**
     * Returns the internal calculator type string (e.g. 'sip', 'swp', 'lumpsum', 'target_corpus', 'all')
     */
    public function getType(): string;

    /**
     * Provides the initial default inputs for the calculator
     */
    public function getInitialInputs(): InvestmentInputs;

    /**
     * Calculates the starting corpus based on inputs
     */
    public function getCorpus(InvestmentInputs $inputs): float;
}
