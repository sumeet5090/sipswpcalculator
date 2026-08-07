<?php

declare(strict_types=1);

namespace Core;

/**
 * InvestmentInputs
 * Encapsulates and sanitizes user input parameters for calculations.
 */
class InvestmentInputs
{
    private float $sip;
    private int $years;
    private float $rate;
    private float $stepup;
    private bool $enableSwp;
    private float $swpWithdrawal;
    private float $swpStepup;
    private int $swpYears;
    private float $lumpsum;
    private float $swpRate;
    private float $inflation;
    private float $ltcgExemption;
    private float $ltcgTaxRate;

    /**
     * Private constructor to enforce factory creation.
     */
    private function __construct(
        float $sip,
        int $years,
        float $rate,
        float $stepup,
        bool $enableSwp,
        float $swpWithdrawal,
        float $swpStepup,
        int $swpYears,
        float $lumpsum,
        float $swpRate,
        float $inflation,
        float $ltcgExemption = 125000.0,
        float $ltcgTaxRate = 0.125
    ) {
        $this->sip = $sip;
        $this->years = $years;
        $this->rate = $rate;
        $this->stepup = $stepup;
        $this->enableSwp = $enableSwp;
        $this->swpWithdrawal = $swpWithdrawal;
        $this->swpStepup = $swpStepup;
        $this->swpYears = $swpYears;
        $this->lumpsum = $lumpsum;
        $this->swpRate = $swpRate;
        $this->inflation = $inflation;
        $this->ltcgExemption = $ltcgExemption;
        $this->ltcgTaxRate = $ltcgTaxRate;
    }

    /**
     * Helper to load the JSON defaults via ConfigService or Container.
     */
    private static function loadDefaults(\Services\ConfigService $config): array
    {
        return $config->getCalculatorDefaults();
    }

    /**
     * Helper method to resolve and clamp a field from payload against config rules.
     */
    private static function resolveField(string $key, array $data, array $cfg): float
    {
        if (isset($data[$key])) {
            $min = (float) ($cfg[$key]['min'] ?? 0);
            $max = (float) ($cfg[$key]['max'] ?? PHP_FLOAT_MAX);
            return self::clamp((float) $data[$key], $min, $max);
        }
        return (float) ($cfg[$key]['default'] ?? 0.0);
    }

    /**
     * Create sanitized inputs from request POST/GET payload.
     * Bounds and defaults are read from the central calculator_defaults.json config.
     *
     * @param array $data Typically $_POST or $_GET payload
     * @param \Services\ConfigService $config ConfigService instance
     * @return self
     */
    public static function fromRequest(array $data, \Services\ConfigService $config): self
    {
        // Load the single source of truth for all bounds and defaults.
        $cfg = self::loadDefaults($config);

        $sip           = self::resolveField('sip', $data, $cfg);
        $years         = (int) self::resolveField('years', $data, $cfg);
        $rate          = self::resolveField('rate', $data, $cfg);
        $stepup        = self::resolveField('stepup', $data, $cfg);
        $enableSwp     = isset($data['enable_swp']) && (bool)$data['enable_swp'];
        $swpWithdrawal = self::resolveField('swp_withdrawal', $data, $cfg);
        $swpStepup     = self::resolveField('swp_stepup', $data, $cfg);
        $swpYears      = (int) self::resolveField('swp_years', $data, $cfg);
        $lumpsum       = self::resolveField('lumpsum', $data, $cfg);
        $swpRate       = self::resolveField('swp_rate', $data, $cfg);
        $inflation     = self::resolveField('inflation', $data, $cfg);

        $ltcgExemption = (float) ($cfg['ltcg_tax']['exemption_threshold'] ?? 125000.0);
        $ltcgTaxRate   = (float) ($cfg['ltcg_tax']['rate'] ?? 0.125);

        return new self(
            $sip,
            $years,
            $rate,
            $stepup,
            $enableSwp,
            $swpWithdrawal,
            $swpStepup,
            $swpYears,
            $lumpsum,
            $swpRate,
            $inflation,
            $ltcgExemption,
            $ltcgTaxRate
        );
    }

    /**
     * Named constructor for the SWP-only calculator.
     *
     * Maps the HTTP `corpus` field → internal `lumpsum` domain concept.
     * SWP is always enabled; SIP accumulation fields default to zero/minimal values.
     * This is the industry-standard Named Constructor pattern: one input shape → one factory.
     *
     * @param array $data POST/GET payload from the SWP calculator form
     * @param \Services\ConfigService $config ConfigService instance
     * @return self
     */
    public static function fromSwpRequest(array $data, \Services\ConfigService $config): self
    {
        $cfg = self::loadDefaults($config);

        $corpus        = self::resolveField('corpus', $data, $cfg);
        $swpWithdrawal = self::resolveField('swp_withdrawal', $data, $cfg);
        $swpStepup     = self::resolveField('swp_stepup', $data, $cfg);
        $swpYears      = (int) self::resolveField('swp_years', $data, $cfg);
        $swpRate       = self::resolveField('swp_rate', $data, $cfg);
        $inflation     = self::resolveField('inflation', $data, $cfg);

        $ltcgExemption = (float) ($cfg['ltcg_tax']['exemption_threshold'] ?? 125000.0);
        $ltcgTaxRate   = (float) ($cfg['ltcg_tax']['rate'] ?? 0.125);

        return new self(
            0.0,
            0,
            0.0,
            0.0,
            true,
            $swpWithdrawal,
            $swpStepup,
            $swpYears,
            $corpus,         // corpus maps to lumpsum as starting balance
            $swpRate,
            $inflation,
            $ltcgExemption,
            $ltcgTaxRate
        );
    }

    /**
     * Clamp a numeric value to constraints.
     */
    private static function clamp(float $val, float $min, float $max): float
    {
        return max($min, min($max, $val));
    }

    public function getSip(): float
    {
        return $this->sip;
    }

    public function getYears(): int
    {
        return $this->years;
    }

    public function getRate(): float
    {
        return $this->rate;
    }

    public function getStepup(): float
    {
        return $this->stepup;
    }

    public function isSwpEnabled(): bool
    {
        return $this->enableSwp;
    }

    public function getSwpWithdrawal(): float
    {
        return $this->swpWithdrawal;
    }

    public function getSwpStepup(): float
    {
        return $this->swpStepup;
    }

    public function getSwpYears(): int
    {
        return $this->swpYears;
    }

    public function getLumpsum(): float
    {
        return $this->lumpsum;
    }

    /**
     * Semantic alias for lumpsum representing initial starting portfolio balance/corpus.
     */
    public function getInitialBalance(): float
    {
        return $this->lumpsum;
    }

    /**
     * Semantic alias for SWP initial starting corpus.
     */
    public function getInitialCorpus(): float
    {
        return $this->lumpsum;
    }

    public function getSwpRate(): float
    {
        return $this->swpRate;
    }

    public function getInflation(): float
    {
        return $this->inflation;
    }

    public function getLtcgExemption(): float
    {
        return $this->ltcgExemption;
    }

    public function getLtcgTaxRate(): float
    {
        return $this->ltcgTaxRate;
    }
}
