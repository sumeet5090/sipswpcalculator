<?php

declare(strict_types=1);

namespace Core;

/**
 * InvestmentInputs
 * Encapsulates and sanitizes user input parameters for calculations.
 */
class InvestmentInputs
{
    public const DEFAULT_LTCG_EXEMPTION = 125000.0;
    public const DEFAULT_LTCG_TAX_RATE = 0.125;

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
        float $ltcgExemption = self::DEFAULT_LTCG_EXEMPTION,
        float $ltcgTaxRate = self::DEFAULT_LTCG_TAX_RATE
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
     * Helper method to resolve LTCG tax exemption and rate from config.
     *
     * @return array{0: float, 1: float} [exemption_threshold, rate]
     */
    private static function resolveLtcgConfig(array $cfg): array
    {
        $exemption = (float) ($cfg['ltcg_tax']['exemption_threshold'] ?? self::DEFAULT_LTCG_EXEMPTION);
        $rate = (float) ($cfg['ltcg_tax']['rate'] ?? self::DEFAULT_LTCG_TAX_RATE);
        return [$exemption, $rate];
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
        $enableSwp     = filter_var($data['enable_swp'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $swpWithdrawal = self::resolveField('swp_withdrawal', $data, $cfg);
        $swpStepup     = self::resolveField('swp_stepup', $data, $cfg);
        $swpYears      = (int) self::resolveField('swp_years', $data, $cfg);
        $lumpsumField  = (!isset($data['lumpsum']) && isset($data['corpus'])) ? 'corpus' : 'lumpsum';
        $lumpsum       = self::resolveField($lumpsumField, $data, $cfg);
        $swpRate       = self::resolveField('swp_rate', $data, $cfg);
        $inflation     = self::resolveField('inflation', $data, $cfg);

        [$ltcgExemption, $ltcgTaxRate] = self::resolveLtcgConfig($cfg);

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

        [$ltcgExemption, $ltcgTaxRate] = self::resolveLtcgConfig($cfg);

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
     * Named constructor for the Lumpsum-only calculator.
     *
     * @param array $data POST/GET payload from the Lumpsum calculator form
     * @param \Services\ConfigService $config ConfigService instance
     * @return self
     */
    public static function fromLumpsumRequest(array $data, \Services\ConfigService $config): self
    {
        $cfg = self::loadDefaults($config);

        $defaultLumpsum = (float) ($cfg['lumpsum']['default'] ?? 500000.0);
        $lumpsum   = isset($data['lumpsum']) ? self::resolveField('lumpsum', $data, $cfg) : $defaultLumpsum;
        $years     = (int) self::resolveField('years', $data, $cfg);
        $rate      = self::resolveField('rate', $data, $cfg);
        $inflation = self::resolveField('inflation', $data, $cfg);

        [$ltcgExemption, $ltcgTaxRate] = self::resolveLtcgConfig($cfg);

        return new self(
            0.0,
            $years,
            $rate,
            0.0,
            false,
            0.0,
            0.0,
            0,
            $lumpsum,
            0.0,
            $inflation,
            $ltcgExemption,
            $ltcgTaxRate
        );
    }

    /**
     * Named constructor to instantiate inputs directly from typed primitive values.
     */
    public static function fromValues(
        float $sip = 0.0,
        int $years = 0,
        float $rate = 0.0,
        float $stepup = 0.0,
        bool $enableSwp = false,
        float $swpWithdrawal = 0.0,
        float $swpStepup = 0.0,
        int $swpYears = 0,
        float $lumpsum = 0.0,
        float $swpRate = 0.0,
        float $inflation = 0.0,
        float $ltcgExemption = self::DEFAULT_LTCG_EXEMPTION,
        float $ltcgTaxRate = self::DEFAULT_LTCG_TAX_RATE
    ): self {
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

    public function withSip(float $sip): self
    {
        $clone = clone $this;
        $clone->sip = $sip;
        return $clone;
    }

    public function withYears(int $years): self
    {
        $clone = clone $this;
        $clone->years = $years;
        return $clone;
    }

    public function withLumpsum(float $lumpsum): self
    {
        $clone = clone $this;
        $clone->lumpsum = $lumpsum;
        return $clone;
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

    /**
     * Export inputs as an associative array formatted for Twig templates.
     *
     * @return array<string, mixed>
     */
    public function toTemplateData(): array
    {
        return [
            'sip'             => $this->sip,
            'years'           => $this->years,
            'rate'            => $this->rate,
            'stepup'          => $this->stepup,
            'lumpsum'         => $this->lumpsum,
            'corpus'          => $this->lumpsum,
            'enable_swp'      => $this->enableSwp,
            'swp_withdrawal'  => $this->swpWithdrawal,
            'swp_years_input' => $this->swpYears,
            'swp_stepup'      => $this->swpStepup,
            'swp_rate'        => $this->swpRate,
            'inflation'       => $this->inflation,
        ];
    }
}
