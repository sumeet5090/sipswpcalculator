<?php

declare(strict_types=1);

namespace Core;

/**
 * CurrencyHelper
 * Formats numbers in Indian Standard Notation (Lakhs/Crores) prefixed with the Rupee symbol.
 */
class CurrencyHelper implements CurrencyFormatterInterface
{
    /**
     * Format a numeric amount using Indian numbering system notation.
     *
     * @param float|int $num
     * @return string
     */
    public function format(float|int $num): string
    {
        return self::formatInr($num);
    }

    /**
     * Format a numeric amount using Indian numbering system notation.
     *
     * @param float|int $num
     * @return string
     */
    private static function formatInr(float|int $num): string
    {
        $num = round($num);
        $isNegative = $num < 0;
        $money = (int) abs($num);
        $length = strlen((string) $money);
        $delimiter = '';

        $moneyStr = (string) $money;

        if ($length <= 3) {
            $delimiter = $moneyStr;
        } else {
            $lastThree = substr($moneyStr, -3);
            $restUnits = substr($moneyStr, 0, -3);
            $restUnits = (strlen($restUnits) % 2 === 1) ? "0" . $restUnits : $restUnits;

            $firstPart = '';
            $exploded = str_split($restUnits, 2);
            foreach ($exploded as $index => $value) {
                if ($index === 0) {
                    $firstPart .= (int)$value . ",";
                } else {
                    $firstPart .= $value . ",";
                }
            }
            $delimiter = $firstPart . $lastThree;
        }

        $prefix = $isNegative ? "-₹" : "₹";
        return $prefix . $delimiter;
    }
}
