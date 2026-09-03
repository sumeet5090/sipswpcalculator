<?php

declare(strict_types=1);

namespace Core\Math;

/**
 * InflationEngine
 * High-precision future cost projection and purchasing power erosion engine.
 * Computes forward cost inflation FV = PV * (1 + i)^t and reverse purchasing power PV = FV / (1 + i)^t.
 */
final class InflationEngine
{
    /**
     * Compute inflation projections and annual degradation schedule.
     *
     * @param float $presentValue Base amount or current expense (> 0)
     * @param float $inflationRate Annual inflation rate in percent (>= 0)
     * @param int $years Projection horizon in years (>= 0)
     * @return array{
     *     present_value: float,
     *     inflation_rate: float,
     *     years: int,
     *     future_cost: float,
     *     purchasing_power: float,
     *     cost_increase: float,
     *     purchasing_power_loss_percentage: float,
     *     schedule: list<array{
     *         year: int,
     *         future_cost: float,
     *         purchasing_power: float,
     *         purchasing_power_loss_percentage: float
     *     }>
     * }
     */
    public function calculate(float $presentValue, float $inflationRate, int $years): array
    {
        $pv = max(0.0, $presentValue);
        $iPercent = max(0.0, $inflationRate);
        $t = max(0, $years);

        $i = $iPercent / 100.0;

        if ($pv === 0.0 || $t === 0) {
            return [
                'present_value' => round($pv, 2),
                'inflation_rate' => round($iPercent, 2),
                'years' => $t,
                'future_cost' => round($pv, 2),
                'purchasing_power' => round($pv, 2),
                'cost_increase' => 0.0,
                'purchasing_power_loss_percentage' => 0.0,
                'schedule' => []
            ];
        }

        $futureCost = $pv * pow(1.0 + $i, $t);
        $purchasingPower = $pv / pow(1.0 + $i, $t);
        $costIncrease = $futureCost - $pv;
        $powerLossPct = (1.0 - (1.0 / pow(1.0 + $i, $t))) * 100.0;

        $schedule = [];
        for ($year = 1; $year <= $t; $year++) {
            $yearFutureCost = $pv * pow(1.0 + $i, $year);
            $yearPower = $pv / pow(1.0 + $i, $year);
            $yearLossPct = (1.0 - (1.0 / pow(1.0 + $i, $year))) * 100.0;

            $schedule[] = [
                'year' => $year,
                'future_cost' => round($yearFutureCost, 2),
                'purchasing_power' => round($yearPower, 2),
                'purchasing_power_loss_percentage' => round($yearLossPct, 2)
            ];
        }

        return [
            'present_value' => round($pv, 2),
            'inflation_rate' => round($iPercent, 2),
            'years' => $t,
            'future_cost' => round($futureCost, 2),
            'purchasing_power' => round($purchasingPower, 2),
            'cost_increase' => round($costIncrease, 2),
            'purchasing_power_loss_percentage' => round($powerLossPct, 2),
            'schedule' => $schedule
        ];
    }
}
