<?php
declare(strict_types=1);

/**
 * functions.php
 * Wrapper functions for backwards compatibility.
 */

require_once __DIR__ . '/src/Core/CurrencyHelper.php';

/**
 * Format numbers in Indian Standard Notation (Lakhs/Crores)
 *
 * @param float|int $num
 * @return string
 */
function formatInr(float|int $num): string
{
    return \Core\CurrencyHelper::formatInr($num);
}