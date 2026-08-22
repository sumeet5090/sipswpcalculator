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

        $referrerDist = $stats['referrerDist'] ?? [];
        $studioTabDist = $stats['studioTabDist'] ?? [];
        $strategyStarterDist = $stats['strategyStarterDist'] ?? [];

        $formatLabel = static function (string $val): string {
            return ucwords(str_replace(['_', '-'], ' ', $val));
        };

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
            'avgScrollDepth'      => number_format((float) ($stats['avgScrollDepth'] ?? 0), 1),
            'avgDwellTime'        => (int) round((float) ($stats['avgDwellTime'] ?? 0)),

            // Chart.js JSON Payloads
            'volumeLabels'       => $this->encodeJson(array_column($dailyVolume, 'day')),
            'volumeData'         => $this->encodeJson(array_map('intval', array_column($dailyVolume, 'cnt'))),

            'currencyLabels'     => $this->encodeJson(array_column($currencyDist, 'currency')),
            'currencyData'       => $this->encodeJson(array_map('intval', array_column($currencyDist, 'cnt'))),
            'currencyColorsJson' => $this->encodeJson($currencyColors),

            'stepUpDoughnutData' => $this->encodeJson([$stats['stepUpSIP'] ?? 0, $stats['flatSIP'] ?? 0]),

            'durationLabels'     => $this->encodeJson(array_column($durationDist, 'bucket')),
            'durationData'       => $this->encodeJson(array_map('intval', array_column($durationDist, 'cnt'))),

            'ambitionLabels'     => $this->encodeJson(array_column($ambitionBuckets, 'goal_bucket')),
            'ambitionData'       => $this->encodeJson(array_map('intval', array_column($ambitionBuckets, 'cnt'))),

            'deviceLabels'       => $this->encodeJson(array_map('ucfirst', array_column($stats['deviceDist'] ?? [], 'device'))),
            'deviceData'         => $this->encodeJson(array_map('intval', array_column($stats['deviceDist'] ?? [], 'cnt'))),

            'goalModeLabels'     => $this->encodeJson(array_map('ucfirst', array_column($stats['goalModeDist'] ?? [], 'mode'))),
            'goalModeData'       => $this->encodeJson(array_map('intval', array_column($stats['goalModeDist'] ?? [], 'cnt'))),

            'referrerLabels'     => $this->encodeJson(array_map($formatLabel, array_column($referrerDist, 'ref'))),
            'referrerData'       => $this->encodeJson(array_map('intval', array_column($referrerDist, 'cnt'))),

            'studioTabLabels'    => $this->encodeJson(array_map($formatLabel, array_column($studioTabDist, 'tab'))),
            'studioTabData'      => $this->encodeJson(array_map('intval', array_column($studioTabDist, 'cnt'))),

            'strategyStarterLabels' => $this->encodeJson(array_map($formatLabel, array_column($strategyStarterDist, 'preset'))),
            'strategyStarterData'   => $this->encodeJson(array_map('intval', array_column($strategyStarterDist, 'cnt'))),
        ];
    }

    private function encodeJson(mixed $data): string
    {
        $json = json_encode(
            $data,
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        );
        return is_string($json) ? $json : '[]';
    }
}
