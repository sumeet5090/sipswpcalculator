<?php

declare(strict_types=1);

namespace Core;

/**
 * InsightPayload
 * Data Transfer Object for anonymized calculation analytics payload.
 */
readonly class InsightPayload
{
    public function __construct(
        public string $calcType,
        public float $amount,
        public int $duration,
        public float $stepUpPct = 0.0,
        public ?string $currency = null,
        public bool $pdfDownloaded = false,
        public ?float $interestRate = null,
        public ?float $sipAmount = null,
        public ?int $sipDuration = null,
        public ?float $sipStepUp = null,
        public int $swpEnabled = 0,
        public ?float $swpWithdrawal = null,
        public ?int $swpDuration = null,
        public ?float $swpStepUp = null,
        public ?float $finalCorpus = null,
        public ?float $totalInvested = null,
        public ?float $wealthMultiplier = null,
        public ?string $goalMode = null,
        public ?string $deviceType = null,
        public int $tableViewed = 0,
        public int $pdfHasCustomName = 0,
        public int $inflationEnabled = 0,
        public int $interactionCount = 1,
        public string $presetClicked = 'none',
        public string $exitAction = 'calc_only',
        public string $landingPath = '/',
        public string $referrerCategory = 'direct',
        public ?string $utmSource = null,
        public ?string $utmMedium = null,
        public int $scrollDepthPct = 0,
        public int $dwellTimeSeconds = 0,
        public int $quickAnswerViewed = 0,
        public string $faqItemExpanded = 'none',
        public string $glossaryTermClicked = 'none',
        public string $hudShortcutClicked = 'none',
        public string $activeStudioTab = 'city_benchmark',
        public string $strategyStarterUsed = 'none',
        public int $guidedWizardCompleted = 0,
        public string $stressTestScenario = 'none',
        public string $cityBenchmarkCity = 'none',
        public int $scenarioDiffSaved = 0,
        public int $csvExported = 0,
        public int $qrModalOpened = 0,
        public int $taxWaterfallOpened = 0,
        public int $goalPledgeCreated = 0,
        public string $internalHubClicked = 'none',
        public ?int $cwvLcpMs = null,
        public ?float $cwvCls = null,
        public ?int $cwvInpMs = null,
        public ?string $connectionSpeed = null,
        public ?string $viewportBucket = 'desktop'
    ) {
    }

    /**
     * Create an InsightPayload instance from a decoded JSON request array.
     */
    public static function fromArray(array $data): self
    {
        $currency = (!empty($data['currency']) && is_string($data['currency']))
            ? mb_substr(trim(strtoupper($data['currency'])), 0, 10, 'UTF-8')
            : 'INR';

        $pdfDownloaded = false;
        if (isset($data['pdf_downloaded'])) {
            $pdfDownloaded = is_bool($data['pdf_downloaded'])
                ? $data['pdf_downloaded']
                : (filter_var($data['pdf_downloaded'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false);
        }

        $rawCalcType = isset($data['calc_type']) && is_string($data['calc_type']) ? trim($data['calc_type']) : 'SIP';
        $calcType = mb_substr($rawCalcType !== '' ? $rawCalcType : 'SIP', 0, 32, 'UTF-8');

        $interactionCount = isset($data['interaction_count']) && is_numeric($data['interaction_count'])
            ? max(1, (int) $data['interaction_count'])
            : 1;

        $presetClicked = self::toStringOrNull($data, 'preset_clicked', 64) ?? 'none';
        $exitAction = self::toStringOrNull($data, 'exit_action', 64) ?? 'calc_only';

        $rawScrollDepth = isset($data['scroll_depth_pct']) && is_numeric($data['scroll_depth_pct'])
            ? (int) $data['scroll_depth_pct']
            : 0;
        $scrollDepthPct = max(0, min(100, $rawScrollDepth));

        $rawDwellTime = isset($data['dwell_time_seconds']) && is_numeric($data['dwell_time_seconds'])
            ? (int) $data['dwell_time_seconds']
            : 0;
        $dwellTimeSeconds = max(0, $rawDwellTime);

        $landingPath = self::toStringOrNull($data, 'landing_path', 128) ?? '/';
        $referrerCategory = self::toStringOrNull($data, 'referrer_category', 32) ?? 'direct';
        $utmSource = self::toStringOrNull($data, 'utm_source', 64);
        $utmMedium = self::toStringOrNull($data, 'utm_medium', 64);

        $faqItemExpanded = self::toStringOrNull($data, 'faq_item_expanded', 64) ?? 'none';
        $glossaryTermClicked = self::toStringOrNull($data, 'glossary_term_clicked', 64) ?? 'none';
        $hudShortcutClicked = self::toStringOrNull($data, 'hud_shortcut_clicked', 64) ?? 'none';
        $activeStudioTab = self::toStringOrNull($data, 'active_studio_tab', 64) ?? 'city_benchmark';
        $strategyStarterUsed = self::toStringOrNull($data, 'strategy_starter_used', 64) ?? 'none';
        $stressTestScenario = self::toStringOrNull($data, 'stress_test_scenario', 64) ?? 'none';
        $cityBenchmarkCity = self::toStringOrNull($data, 'city_benchmark_city', 64) ?? 'none';
        $internalHubClicked = self::toStringOrNull($data, 'internal_hub_clicked', 64) ?? 'none';
        $connectionSpeed = self::toStringOrNull($data, 'connection_speed', 16);
        $viewportBucket = self::toStringOrNull($data, 'viewport_bucket', 32) ?? 'desktop';

        return new self(
            calcType: $calcType,
            amount: isset($data['amount']) && is_numeric($data['amount']) ? (float) $data['amount'] : 0.0,
            duration: isset($data['duration']) && is_numeric($data['duration']) ? (int) $data['duration'] : 0,
            stepUpPct: isset($data['step_up_pct']) && is_numeric($data['step_up_pct']) ? (float) $data['step_up_pct'] : 0.0,
            currency: $currency,
            pdfDownloaded: $pdfDownloaded,
            interestRate: self::toFloatOrNull($data, 'interest_rate'),
            sipAmount: self::toFloatOrNull($data, 'sip_amount'),
            sipDuration: self::toIntOrNull($data, 'sip_duration'),
            sipStepUp: self::toFloatOrNull($data, 'sip_step_up'),
            swpEnabled: self::toBoolInt($data, 'swp_enabled'),
            swpWithdrawal: self::toFloatOrNull($data, 'swp_withdrawal'),
            swpDuration: self::toIntOrNull($data, 'swp_duration'),
            swpStepUp: self::toFloatOrNull($data, 'swp_step_up'),
            finalCorpus: self::toFloatOrNull($data, 'final_corpus'),
            totalInvested: self::toFloatOrNull($data, 'total_invested'),
            wealthMultiplier: self::toFloatOrNull($data, 'wealth_multiplier'),
            goalMode: self::toStringOrNull($data, 'goal_mode', 32),
            deviceType: self::toStringOrNull($data, 'device_type', 32),
            tableViewed: self::toBoolInt($data, 'table_viewed'),
            pdfHasCustomName: self::toBoolInt($data, 'pdf_has_custom_name'),
            inflationEnabled: self::toBoolInt($data, 'inflation_enabled'),
            interactionCount: $interactionCount,
            presetClicked: $presetClicked,
            exitAction: $exitAction,
            landingPath: $landingPath,
            referrerCategory: $referrerCategory,
            utmSource: $utmSource,
            utmMedium: $utmMedium,
            scrollDepthPct: $scrollDepthPct,
            dwellTimeSeconds: $dwellTimeSeconds,
            quickAnswerViewed: self::toBoolInt($data, 'quick_answer_viewed'),
            faqItemExpanded: $faqItemExpanded,
            glossaryTermClicked: $glossaryTermClicked,
            hudShortcutClicked: $hudShortcutClicked,
            activeStudioTab: $activeStudioTab,
            strategyStarterUsed: $strategyStarterUsed,
            guidedWizardCompleted: self::toBoolInt($data, 'guided_wizard_completed'),
            stressTestScenario: $stressTestScenario,
            cityBenchmarkCity: $cityBenchmarkCity,
            scenarioDiffSaved: self::toBoolInt($data, 'scenario_diff_saved'),
            csvExported: self::toBoolInt($data, 'csv_exported'),
            qrModalOpened: self::toBoolInt($data, 'qr_modal_opened'),
            taxWaterfallOpened: self::toBoolInt($data, 'tax_waterfall_opened'),
            goalPledgeCreated: self::toBoolInt($data, 'goal_pledge_created'),
            internalHubClicked: $internalHubClicked,
            cwvLcpMs: self::toIntOrNull($data, 'cwv_lcp_ms'),
            cwvCls: self::toFloatOrNull($data, 'cwv_cls'),
            cwvInpMs: self::toIntOrNull($data, 'cwv_inp_ms'),
            connectionSpeed: $connectionSpeed,
            viewportBucket: $viewportBucket
        );
    }

    private static function toBoolInt(array $data, string $key): int
    {
        if (!isset($data[$key])) {
            return 0;
        }
        $val = $data[$key];
        if (is_bool($val)) {
            return $val ? 1 : 0;
        }
        if (is_numeric($val)) {
            return ((int) $val) > 0 ? 1 : 0;
        }
        $filtered = filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        return $filtered === true ? 1 : 0;
    }

    private static function toFloatOrNull(array $data, string $key): ?float
    {
        if (!isset($data[$key]) || $data[$key] === '') {
            return null;
        }
        if (is_numeric($data[$key])) {
            return (float) $data[$key];
        }
        return null;
    }

    private static function toIntOrNull(array $data, string $key): ?int
    {
        if (!isset($data[$key]) || $data[$key] === '') {
            return null;
        }
        if (is_numeric($data[$key])) {
            return (int) $data[$key];
        }
        return null;
    }

    private static function toStringOrNull(array $data, string $key, int $maxLen = 64): ?string
    {
        if (!isset($data[$key]) || !is_string($data[$key])) {
            return null;
        }
        $trimmed = trim($data[$key]);
        if ($trimmed === '') {
            return null;
        }
        return mb_substr($trimmed, 0, $maxLen, 'UTF-8');
    }
}
