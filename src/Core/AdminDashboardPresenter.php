<?php

declare(strict_types=1);

namespace Core;

/**
 * AdminDashboardPresenter
 * Transforms raw database statistics into View Models (specifically, JSON strings)
 * for the Twig view to render blindly, adhering to MVP and POLA.
 */
class AdminDashboardPresenter
{
    /**
     * Format the raw statistics array for the Twig view.
     *
     * @param array $stats
     * @return array
     */
    public function formatForView(array $stats): array
    {
        $dailyVolume = $stats['dailyVolume'] ?? [];
        $currencyDist = $stats['currencyDist'] ?? [];
        $durationDist = $stats['durationDist'] ?? [];
        $ambitionBuckets = $stats['ambitionBuckets'] ?? [];

        // Currency Colors mapping (matching brand emerald/teal)
        $currencyColorMap = [
            'INR' => 'rgba(5, 150, 105, 0.85)', // Emerald 600
            'UNKNOWN' => 'rgba(148, 163, 184, 0.7)', // Slate 400
        ];
        $currencyColors = [];
        foreach (array_column($currencyDist, 'currency') as $c) {
            $currencyColors[] = $currencyColorMap[$c] ?? 'rgba(13, 148, 136, 0.85)'; // Teal 600
        }

        return [
            // KPI Scalars
            'totalCalculations' => $stats['totalCalculations'] ?? 0,
            'avgStepUpPct'      => number_format((float) ($stats['avgStepUpPct'] ?? 0), 1),
            'stepUpSIP'         => $stats['stepUpSIP'] ?? 0,
            'flatSIP'           => $stats['flatSIP'] ?? 0,

            // Chart.js JSON Payloads
            'volumeLabels'       => json_encode(array_column($dailyVolume, 'day')),
            'volumeData'         => json_encode(array_map('intval', array_column($dailyVolume, 'cnt'))),

            'currencyLabels'     => json_encode(array_column($currencyDist, 'currency')),
            'currencyData'       => json_encode(array_map('intval', array_column($currencyDist, 'cnt'))),
            'currencyColorsJson' => json_encode($currencyColors),

            'stepUpDoughnutData' => json_encode([$stats['stepUpSIP'] ?? 0, $stats['flatSIP'] ?? 0]),

            'durationLabels'     => json_encode(array_column($durationDist, 'bucket')),
            'durationData'       => json_encode(array_map('intval', array_column($durationDist, 'cnt'))),

            'ambitionLabels'     => json_encode(array_column($ambitionBuckets, 'goal_bucket')),
            'ambitionData'       => json_encode(array_map('intval', array_column($ambitionBuckets, 'cnt'))),
        ];
    }
}
