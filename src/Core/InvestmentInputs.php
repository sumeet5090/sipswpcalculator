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
        float $swpRate
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
    }

    /**
     * Create sanitized inputs from request POST/GET payload.
     *
     * @param array $data Typically $_POST or $_GET payload
     * @return self
     */
    public static function fromRequest(array $data): self
    {
        // CSRF/Honeypot check is orchestrated at the Controller level.
        // We parse and apply constraints logic here.
        $sip = isset($data['sip']) ? self::clamp((float)$data['sip'], 500, 1000000) : 10000.0;
        $years = isset($data['years']) ? (int)self::clamp((float)$data['years'], 1, 50) : 20;
        $rate = isset($data['rate']) ? self::clamp((float)$data['rate'], 0.1, 30) : 12.0;
        $stepup = isset($data['stepup']) ? self::clamp((float)$data['stepup'], 0, 50) : 10.0;
        $enableSwp = isset($data['enable_swp']) ? (bool)$data['enable_swp'] : false;
        $swpWithdrawal = isset($data['swp_withdrawal']) ? self::clamp((float)$data['swp_withdrawal'], 0, 1000000) : 5000.0;
        $swpStepup = isset($data['swp_stepup']) ? self::clamp((float)$data['swp_stepup'], 0, 20) : 6.0;
        $swpYears = isset($data['swp_years']) ? (int)self::clamp((float)$data['swp_years'], 1, 50) : 20;
        $lumpsum = isset($data['lumpsum']) ? self::clamp((float)$data['lumpsum'], 0, 10000000) : 0.0;
        $swpRate = isset($data['swp_rate']) ? self::clamp((float)$data['swp_rate'], 0.1, 30) : 8.0;

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
            $swpRate
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
}
