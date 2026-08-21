<?php

declare(strict_types=1);

namespace Core;

/**
 * CurrencyFormatterInterface
 * Abstraction contract for formatting monetary values.
 */
interface CurrencyFormatterInterface
{
    /**
     * Format a numeric amount using currency formatting rules.
     *
     * @param float|int $num
     * @return string
     */
    public function format(float|int $num): string;

    /**
     * Get symbol for a given currency code.
     *
     * @param string $currency
     * @return string
     */
    public function getSymbol(string $currency): string;
}
