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
        public string $exitAction = 'calc_only'
    ) {
    }

    /**
     * Create an InsightPayload instance from a decoded JSON request array.
     */
    public static function fromArray(array $data): self
    {
        $toBoolInt = function (string $key) use ($data): int {
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
        };

        $toFloatOrNull = function (string $key) use ($data): ?float {
            if (!isset($data[$key]) || $data[$key] === '') {
                return null;
            }
            if (is_numeric($data[$key])) {
                return (float) $data[$key];
            }
            return null;
        };

        $toIntOrNull = function (string $key) use ($data): ?int {
            if (!isset($data[$key]) || $data[$key] === '') {
                return null;
            }
            if (is_numeric($data[$key])) {
                return (int) $data[$key];
            }
            return null;
        };

        $toStringOrNull = function (string $key, int $maxLen = 64) use ($data): ?string {
            if (!isset($data[$key]) || !is_string($data[$key])) {
                return null;
            }
            $trimmed = trim($data[$key]);
            if ($trimmed === '') {
                return null;
            }
            return mb_substr($trimmed, 0, $maxLen, 'UTF-8');
        };

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

        $presetClicked = $toStringOrNull('preset_clicked', 64) ?? 'none';
        $exitAction = $toStringOrNull('exit_action', 64) ?? 'calc_only';

        return new self(
            calcType: $calcType,
            amount: isset($data['amount']) && is_numeric($data['amount']) ? (float) $data['amount'] : 0.0,
            duration: isset($data['duration']) && is_numeric($data['duration']) ? (int) $data['duration'] : 0,
            stepUpPct: isset($data['step_up_pct']) && is_numeric($data['step_up_pct']) ? (float) $data['step_up_pct'] : 0.0,
            currency: $currency,
            pdfDownloaded: $pdfDownloaded,
            interestRate: $toFloatOrNull('interest_rate'),
            sipAmount: $toFloatOrNull('sip_amount'),
            sipDuration: $toIntOrNull('sip_duration'),
            sipStepUp: $toFloatOrNull('sip_step_up'),
            swpEnabled: $toBoolInt('swp_enabled'),
            swpWithdrawal: $toFloatOrNull('swp_withdrawal'),
            swpDuration: $toIntOrNull('swp_duration'),
            swpStepUp: $toFloatOrNull('swp_step_up'),
            finalCorpus: $toFloatOrNull('final_corpus'),
            totalInvested: $toFloatOrNull('total_invested'),
            wealthMultiplier: $toFloatOrNull('wealth_multiplier'),
            goalMode: $toStringOrNull('goal_mode', 32),
            deviceType: $toStringOrNull('device_type', 32),
            tableViewed: $toBoolInt('table_viewed'),
            pdfHasCustomName: $toBoolInt('pdf_has_custom_name'),
            inflationEnabled: $toBoolInt('inflation_enabled'),
            interactionCount: $interactionCount,
            presetClicked: $presetClicked,
            exitAction: $exitAction
        );
    }
}
