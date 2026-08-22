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
     * Format dynamic large amounts with appropriate Lakh/Crore or Million/Billion suffix.
     *
     * @param float|int $amount
     * @param string $currency
     * @return string
     */
    public function formatDynamic(float|int $amount, string $currency = 'INR'): string
    {
        $rounded = round((float) $amount);
        $absAmount = abs($rounded);
        $isNegative = ($absAmount > 0.0) && ($rounded < 0.0);
        $sym = $this->getSymbol($currency);
        $prefix = $isNegative ? "-{$sym}" : $sym;

        if (strtoupper($currency) === 'INR') {
            if ($absAmount >= 10000000.0) {
                $val = number_format($absAmount / 10000000.0, 2, '.', '');
                $val = preg_replace('/\.00$/', '', $val);
                return $prefix . $val . ' Crore';
            }
            if ($absAmount >= 100000.0) {
                $val = number_format($absAmount / 100000.0, 2, '.', '');
                $val = preg_replace('/\.00$/', '', $val);
                return $prefix . $val . ' Lakh';
            }
            if ($absAmount >= 1000.0) {
                $val = number_format($absAmount / 1000.0, 2, '.', '');
                $val = preg_replace('/\.00$/', '', $val);
                return $prefix . $val . 'k';
            }
        } else {
            if ($absAmount >= 1000000000.0) {
                $val = number_format($absAmount / 1000000000.0, 2, '.', '');
                $val = preg_replace('/\.00$/', '', $val);
                return $prefix . $val . 'B';
            }
            if ($absAmount >= 1000000.0) {
                $val = number_format($absAmount / 1000000.0, 2, '.', '');
                $val = preg_replace('/\.00$/', '', $val);
                return $prefix . $val . 'M';
            }
            if ($absAmount >= 1000.0) {
                $val = number_format($absAmount / 1000.0, 2, '.', '');
                $val = preg_replace('/\.00$/', '', $val);
                return $prefix . $val . 'k';
            }
        }

        return $this->format($amount);
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
        $money = (int) abs($num);
        $isNegative = ($money > 0) && ($num < 0);
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

    /**
     * Get symbol for a given currency code.
     */
    public function getSymbol(string $currency): string
    {
        $code = strtoupper(trim($currency));
        $symbolMap = [
            'INR' => '₹',
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'AED' => 'AED',
            'CAD' => '$',
            'AUD' => '$',
        ];
        return $symbolMap[$code] ?? '₹';
    }
}
