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
    private array $currencyColorMap;

    public function __construct(array $currencyColorMap = [])
    {
        $this->currencyColorMap = array_merge([
            'INR' => 'rgba(5, 150, 105, 0.85)', // Emerald 600
            'UNKNOWN' => 'rgba(148, 163, 184, 0.7)', // Slate 400
        ], $currencyColorMap);
    }

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

        $currencyColorMap = $this->currencyColorMap;
        $currencyColors = [];
        foreach (array_column($currencyDist, 'currency') as $c) {
            $currencyColors[] = $currencyColorMap[$c] ?? 'rgba(13, 148, 136, 0.85)'; // Teal 600
        }

        return [
            // KPI Scalars
            'totalCalculations'   => $stats['totalCalculations'] ?? 0,
            'avgStepUpPct'        => number_format((float) ($stats['avgStepUpPct'] ?? 0), 1),
            'stepUpSIP'           => $stats['stepUpSIP'] ?? 0,
            'flatSIP'             => $stats['flatSIP'] ?? 0,
            'tableViewEngagement' => number_format((float) ($stats['tableViewEngagement'] ?? 0), 1),
            'avgFinalCorpus'      => (float) ($stats['avgFinalCorpus'] ?? 0),
            'avgWealthMultiplier' => number_format((float) ($stats['avgWealthMultiplier'] ?? 0), 2),
            'b2bAdvisorRate'      => number_format((float) ($stats['b2bAdvisorRate'] ?? 0), 1),
            'inflationRate'       => number_format((float) ($stats['inflationRate'] ?? 0), 1),
            'avgIterations'       => number_format((float) ($stats['avgIterations'] ?? 1.0), 1),

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

            'deviceLabels'       => json_encode(array_map('ucfirst', array_column($stats['deviceDist'] ?? [], 'device'))),
            'deviceData'         => json_encode(array_map('intval', array_column($stats['deviceDist'] ?? [], 'cnt'))),

            'goalModeLabels'     => json_encode(array_map('ucfirst', array_column($stats['goalModeDist'] ?? [], 'mode'))),
            'goalModeData'       => json_encode(array_map('intval', array_column($stats['goalModeDist'] ?? [], 'cnt'))),
        ];
    }
}
