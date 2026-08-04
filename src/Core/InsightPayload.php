<?php

declare(strict_types=1);

namespace Core;

/**
 * InsightPayload
 * Data Transfer Object for anonymized calculation analytics payload.
 */
class InsightPayload
{
    public function __construct(
        public readonly string $calcType,
        public readonly float $amount,
        public readonly int $duration,
        public readonly float $stepUpPct = 0.0,
        public readonly ?string $currency = null,
        public readonly bool $pdfDownloaded = false,
        public readonly ?float $interestRate = null,
        public readonly ?float $sipAmount = null,
        public readonly ?int $sipDuration = null,
        public readonly ?float $sipStepUp = null,
        public readonly int $swpEnabled = 0,
        public readonly ?float $swpWithdrawal = null,
        public readonly ?int $swpDuration = null,
        public readonly ?float $swpStepUp = null,
        public readonly ?float $finalCorpus = null,
        public readonly ?float $totalInvested = null,
        public readonly ?float $wealthMultiplier = null,
        public readonly ?string $goalMode = null,
        public readonly ?string $deviceType = null,
        public readonly int $tableViewed = 0,
        public readonly int $pdfHasCustomName = 0,
        public readonly int $inflationEnabled = 0,
        public readonly int $interactionCount = 1,
        public readonly string $presetClicked = 'none',
        public readonly string $exitAction = 'calc_only'
    ) {
    }

    /**
     * Create an InsightPayload instance from a decoded JSON request array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            calcType: (string) ($data['calc_type'] ?? 'SIP'),
            amount: (float) ($data['amount'] ?? 0.0),
            duration: (int) ($data['duration'] ?? 0),
            stepUpPct: (float) ($data['step_up_pct'] ?? 0.0),
            currency: isset($data['currency']) ? (string) $data['currency'] : 'INR',
            pdfDownloaded: !empty($data['pdf_downloaded']),
            interestRate: isset($data['interest_rate']) ? (float) $data['interest_rate'] : null,
            sipAmount: isset($data['sip_amount']) ? (float) $data['sip_amount'] : null,
            sipDuration: isset($data['sip_duration']) ? (int) $data['sip_duration'] : null,
            sipStepUp: isset($data['sip_step_up']) ? (float) $data['sip_step_up'] : null,
            swpEnabled: !empty($data['swp_enabled']) ? 1 : 0,
            swpWithdrawal: isset($data['swp_withdrawal']) ? (float) $data['swp_withdrawal'] : null,
            swpDuration: isset($data['swp_duration']) ? (int) $data['swp_duration'] : null,
            swpStepUp: isset($data['swp_step_up']) ? (float) $data['swp_step_up'] : null,
            finalCorpus: isset($data['final_corpus']) ? (float) $data['final_corpus'] : null,
            totalInvested: isset($data['total_invested']) ? (float) $data['total_invested'] : null,
            wealthMultiplier: isset($data['wealth_multiplier']) ? (float) $data['wealth_multiplier'] : null,
            goalMode: isset($data['goal_mode']) ? (string) $data['goal_mode'] : null,
            deviceType: isset($data['device_type']) ? (string) $data['device_type'] : null,
            tableViewed: !empty($data['table_viewed']) ? 1 : 0,
            pdfHasCustomName: !empty($data['pdf_has_custom_name']) ? 1 : 0,
            inflationEnabled: !empty($data['inflation_enabled']) ? 1 : 0,
            interactionCount: isset($data['interaction_count']) ? (int) $data['interaction_count'] : 1,
            presetClicked: isset($data['preset_clicked']) ? (string) $data['preset_clicked'] : 'none',
            exitAction: isset($data['exit_action']) ? (string) $data['exit_action'] : 'calc_only'
        );
    }
}
