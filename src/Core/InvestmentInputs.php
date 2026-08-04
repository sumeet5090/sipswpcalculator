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
        float $inflation
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
    }

    /**
     * Helper to load the JSON defaults via ConfigService or Container.
     */
    private static function loadDefaults(\Services\ConfigService $config): array
    {
        return $config->getCalculatorDefaults();
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

        $sip           = isset($data['sip'])
            ? self::clamp((float)$data['sip'], $cfg['sip']['min'], $cfg['sip']['max'])
            : (float)$cfg['sip']['default'];

        $years         = isset($data['years'])
            ? (int)self::clamp((float)$data['years'], $cfg['years']['min'], $cfg['years']['max'])
            : (int)$cfg['years']['default'];

        $rate          = isset($data['rate'])
            ? self::clamp((float)$data['rate'], $cfg['rate']['min'], $cfg['rate']['max'])
            : (float)$cfg['rate']['default'];

        $stepup        = isset($data['stepup'])
            ? self::clamp((float)$data['stepup'], $cfg['stepup']['min'], $cfg['stepup']['max'])
            : (float)$cfg['stepup']['default'];

        $enableSwp     = isset($data['enable_swp']) && (bool)$data['enable_swp'];

        $swpWithdrawal = isset($data['swp_withdrawal'])
            ? self::clamp((float)$data['swp_withdrawal'], $cfg['swp_withdrawal']['min'], $cfg['swp_withdrawal']['max'])
            : (float)$cfg['swp_withdrawal']['default'];

        $swpStepup     = isset($data['swp_stepup'])
            ? self::clamp((float)$data['swp_stepup'], $cfg['swp_stepup']['min'], $cfg['swp_stepup']['max'])
            : (float)$cfg['swp_stepup']['default'];

        $swpYears      = isset($data['swp_years'])
            ? (int)self::clamp((float)$data['swp_years'], $cfg['swp_years']['min'], $cfg['swp_years']['max'])
            : (int)$cfg['swp_years']['default'];

        $lumpsum       = isset($data['lumpsum'])
            ? self::clamp((float)$data['lumpsum'], $cfg['lumpsum']['min'], $cfg['lumpsum']['max'])
            : (float)$cfg['lumpsum']['default'];

        $swpRate       = isset($data['swp_rate'])
            ? self::clamp((float)$data['swp_rate'], $cfg['swp_rate']['min'], $cfg['swp_rate']['max'])
            : (float)$cfg['swp_rate']['default'];

        $inflation       = isset($data['inflation'])
            ? self::clamp((float)$data['inflation'], $cfg['inflation']['min'], $cfg['inflation']['max'])
            : (float)$cfg['inflation']['default'];

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
            $inflation
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

        // `corpus` is the SWP-specific field — maps to internal `lumpsum` (starting balance).
        $corpus = isset($data['corpus'])
            ? self::clamp((float)$data['corpus'], $cfg['corpus']['min'], $cfg['corpus']['max'])
            : (float)$cfg['corpus']['default'];

        $swpWithdrawal = isset($data['swp_withdrawal'])
            ? self::clamp((float)$data['swp_withdrawal'], $cfg['swp_withdrawal']['min'], $cfg['swp_withdrawal']['max'])
            : (float)$cfg['swp_withdrawal']['default'];

        $swpStepup = isset($data['swp_stepup'])
            ? self::clamp((float)$data['swp_stepup'], $cfg['swp_stepup']['min'], $cfg['swp_stepup']['max'])
            : (float)$cfg['swp_stepup']['default'];

        $swpYears = isset($data['swp_years'])
            ? (int)self::clamp((float)$data['swp_years'], $cfg['swp_years']['min'], $cfg['swp_years']['max'])
            : (int)$cfg['swp_years']['default'];

        $swpRate = isset($data['swp_rate'])
            ? self::clamp((float)$data['swp_rate'], $cfg['swp_rate']['min'], $cfg['swp_rate']['max'])
            : (float)$cfg['swp_rate']['default'];

        $inflation = isset($data['inflation'])
            ? self::clamp((float)$data['inflation'], $cfg['inflation']['min'], $cfg['inflation']['max'])
            : (float)$cfg['inflation']['default'];

        return new self(
            0.0,
            1,
            (float)$cfg['rate']['default'],
            0.0,
            true,
            $swpWithdrawal,
            $swpStepup,
            $swpYears,
            $corpus,         // corpus maps to lumpsum as starting balance
            $swpRate,
            $inflation
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

    public function getSwpRate(): float
    {
        return $this->swpRate;
    }

    public function getInflation(): float
    {
        return $this->inflation;
    }
}
