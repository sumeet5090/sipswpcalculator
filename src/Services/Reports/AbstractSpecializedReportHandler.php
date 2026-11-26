<?php

declare(strict_types=1);

namespace Services\Reports;

use Core\CurrencyFormatterInterface;

/**
 * Shared input coercion, monetary formatting, and HTML ledger building primitives
 * for specialized calculator report handlers.
 */
abstract class AbstractSpecializedReportHandler implements SpecializedReportHandlerInterface
{
    protected CurrencyFormatterInterface $currencyFormatter;

    public function __construct(CurrencyFormatterInterface $currencyFormatter)
    {
        $this->currencyFormatter = $currencyFormatter;
    }

    /**
     * @param array<string, mixed> $post
     */
    protected function readFloat(array $post, string $key, float $default): float
    {
        if (!array_key_exists($key, $post) || $post[$key] === '' || $post[$key] === null) {
            return $default;
        }
        $val = $post[$key];
        return is_numeric($val) ? (float) $val : $default;
    }

    /**
     * @param array<string, mixed> $post
     */
    protected function readInt(array $post, string $key, int $default): int
    {
        if (!array_key_exists($key, $post) || $post[$key] === '' || $post[$key] === null) {
            return $default;
        }
        $val = $post[$key];
        return is_numeric($val) ? (int) $val : $default;
    }

    /**
     * @param array<string, mixed> $post
     */
    protected function readBool(array $post, string $key, bool $default): bool
    {
        if (!array_key_exists($key, $post) || $post[$key] === '' || $post[$key] === null) {
            return $default;
        }
        $parsed = filter_var($post[$key], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        return $parsed ?? $default;
    }

    /**
     * @param array<string, mixed> $post
     */
    protected function readStr(array $post, string $key, string $default): string
    {
        if (!array_key_exists($key, $post) || $post[$key] === '' || $post[$key] === null) {
            return $default;
        }
        $val = $post[$key];
        return is_scalar($val) ? (string) $val : $default;
    }

    protected function formatMoney(float|int $amount, string $symbol): string
    {
        if ($symbol === '₹' || $symbol === '') {
            return $this->currencyFormatter->format($amount);
        }
        $rounded = round((float) $amount);
        $sign = $rounded < 0 ? '-' : '';
        return $sign . $symbol . number_format(abs($rounded));
    }

    /**
     * @param list<string> $headers
     * @param list<list<string>> $rows
     */
    protected function buildHtmlTable(array $headers, array $rows): string
    {
        $html = '<table class="breakdown-table"><thead><tr>';
        foreach ($headers as $idx => $header) {
            $align = $idx === 0 ? 'left' : 'right';
            $html .= '<th style="text-align: ' . $align . ';">' . htmlspecialchars($header) . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($row as $idx => $cell) {
                $align = $idx === 0 ? 'left' : 'right';
                $weight = ($idx === 0 || $idx === count($row) - 1) ? 'font-weight: bold; color: #0f172a;' : '';
                $html .= '<td style="text-align: ' . $align . '; ' . $weight . '">' . htmlspecialchars($cell) . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
        return $html;
    }
}
