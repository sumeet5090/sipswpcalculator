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
}
