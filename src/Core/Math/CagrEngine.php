<?php

declare(strict_types=1);

namespace Core\Math;

/**
 * CagrEngine
 * High-precision Compound Annual Growth Rate (CAGR) and absolute return engine.
 * Computes CAGR = (V_final / V_begin)^(1 / t) - 1.
 */
final class CagrEngine
{
    /**
     * Compute CAGR and return metric suite.
     *
     * @param float $beginningValue Starting portfolio / asset value (> 0)
     * @param float $endingValue Final portfolio / redemption value (>= 0)
     * @param float $years Time period in years (> 0, supports fractional years like 3.5)
     * @return array{
     *     beginning_value: float,
     *     ending_value: float,
     *     years: float,
     *     cagr_percentage: float,
     *     absolute_return_percentage: float,
     *     total_gain: float,
     *     multiplier: float
     * }
     */
    public function calculate(float $beginningValue, float $endingValue, float $years): array
    {
        if ($beginningValue <= 0.0) {
            throw new \InvalidArgumentException('Beginning value must be strictly greater than 0.');
        }

        if ($years <= 0.0) {
            throw new \InvalidArgumentException('Investment duration must be strictly greater than 0 years.');
        }

        $v0 = $beginningValue;
        $vt = max(0.0, $endingValue);
        $t = $years;

        $totalGain = $vt - $v0;
        $absoluteReturn = ($totalGain / $v0) * 100.0;
        $multiplier = $vt / $v0;

        if ($vt === 0.0) {
            $cagr = -100.0;
        } else {
            $cagr = (pow($vt / $v0, 1.0 / $t) - 1.0) * 100.0;
        }

        return [
            'beginning_value' => round($v0, 2),
            'ending_value' => round($vt, 2),
            'years' => round($t, 2),
            'cagr_percentage' => round($cagr, 4),
            'absolute_return_percentage' => round($absoluteReturn, 4),
            'total_gain' => round($totalGain, 2),
            'multiplier' => round($multiplier, 4)
        ];
    }
}
