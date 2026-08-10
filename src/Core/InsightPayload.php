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
        $toBoolInt = fn(string $key): int => (!empty($data[$key]) && $data[$key] !== 'false') ? 1 : 0;

        return new self(
            calcType: (string) ($data['calc_type'] ?? 'SIP'),
            amount: (float) ($data['amount'] ?? 0.0),
            duration: (int) ($data['duration'] ?? 0),
            stepUpPct: (float) ($data['step_up_pct'] ?? 0.0),
            currency: array_key_exists('currency', $data) ? (string) $data['currency'] : 'INR',
            pdfDownloaded: !empty($data['pdf_downloaded']) && $data['pdf_downloaded'] !== 'false',
            interestRate: isset($data['interest_rate']) ? (float) $data['interest_rate'] : null,
            sipAmount: isset($data['sip_amount']) ? (float) $data['sip_amount'] : null,
            sipDuration: isset($data['sip_duration']) ? (int) $data['sip_duration'] : null,
            sipStepUp: isset($data['sip_step_up']) ? (float) $data['sip_step_up'] : null,
            swpEnabled: $toBoolInt('swp_enabled'),
            swpWithdrawal: isset($data['swp_withdrawal']) ? (float) $data['swp_withdrawal'] : null,
            swpDuration: isset($data['swp_duration']) ? (int) $data['swp_duration'] : null,
            swpStepUp: isset($data['swp_step_up']) ? (float) $data['swp_step_up'] : null,
            finalCorpus: isset($data['final_corpus']) ? (float) $data['final_corpus'] : null,
            totalInvested: isset($data['total_invested']) ? (float) $data['total_invested'] : null,
            wealthMultiplier: isset($data['wealth_multiplier']) ? (float) $data['wealth_multiplier'] : null,
            goalMode: isset($data['goal_mode']) ? (string) $data['goal_mode'] : null,
            deviceType: isset($data['device_type']) ? (string) $data['device_type'] : null,
            tableViewed: $toBoolInt('table_viewed'),
            pdfHasCustomName: $toBoolInt('pdf_has_custom_name'),
            inflationEnabled: $toBoolInt('inflation_enabled'),
            interactionCount: isset($data['interaction_count']) ? (int) $data['interaction_count'] : 1,
            presetClicked: isset($data['preset_clicked']) ? (string) $data['preset_clicked'] : 'none',
            exitAction: isset($data['exit_action']) ? (string) $data['exit_action'] : 'calc_only'
        );
    }
}
