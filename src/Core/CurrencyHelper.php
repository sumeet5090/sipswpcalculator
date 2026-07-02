<?php
declare(strict_types=1);

namespace Core;

/**
 * CurrencyHelper
 * Formats numbers in Indian Standard Notation (Lakhs/Crores) prefixed with the Rupee symbol.
 */
class CurrencyHelper
{
    /**
     * Format a numeric amount using Indian numbering system notation.
     *
     * @param float|int $num
     * @return string
     */
    public static function formatInr(float|int $num): string
    {
        $num = round($num);
        $money = floor($num);
        $length = strlen((string) $money);
        $delimiter = '';

        $money = (string) $money;

        if ($length <= 3) {
            $delimiter = $money;
        } else {
            $lastThree = substr($money, -3);
            $restUnits = substr($money, 0, -3);
            $restUnits = (strlen($restUnits) % 2 == 1) ? "0" . $restUnits : $restUnits;
            
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
        
        return "₹ " . $delimiter;
    }
}
