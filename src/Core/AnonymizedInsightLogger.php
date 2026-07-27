<?php

declare(strict_types=1);

namespace Core;

use PDO;

/**
 * Privacy-First Anonymized Insight Logger
 *
 * Logs anonymous usage statistics without storing IP addresses or PII.
 */
class AnonymizedInsightLogger
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? DatabaseManager::getConnection();
    }

    /**
     * Executes the non-blocking logging logic.
     *
     * CRITICAL: Must be called after the calculation results are displayed to the user
     * to avoid slowing down initial page load (0.7s LCP constraint).
     */
    public function logCalculation(
        string $calcType,
        float $amount,
        int $duration,
        float $stepUpPct = 0.0,
        ?string $currency = null,
        bool $pdfDownloaded = false,
        ?float $interestRate = null,
        ?float $sipAmount = null,
        ?int $sipDuration = null,
        ?float $sipStepUp = null,
        int $swpEnabled = 0,
        ?float $swpWithdrawal = null,
        ?int $swpDuration = null,
        ?float $swpStepUp = null,
        ?float $finalCorpus = null,
        ?float $totalInvested = null,
        ?float $wealthMultiplier = null,
        ?string $goalMode = null,
        ?string $deviceType = null,
        int $tableViewed = 0,
        int $pdfHasCustomName = 0,
        int $inflationEnabled = 0,
        int $interactionCount = 1,
        ?string $presetClicked = 'none',
        ?string $exitAction = 'calc_only'
    ): void {
        try {
            // Close the current output buffer and send response to client so logging is non-blocking.
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            }

            // Pull Cloudflare country code from server headers (Privacy-First: No IPs are logged)
            $countryCode = $_SERVER['HTTP_CF_IPCOUNTRY'] ?? null;

            // Capture HTTP Referrer for traffic-source analysis (truncated for privacy)
            $referrer = isset($_SERVER['HTTP_REFERER']) ? substr($_SERVER['HTTP_REFERER'], 0, 512) : null;

            // If currency is not explicitly passed, check requests
            if ($currency === null) {
                $currency = $_REQUEST['currency'] ?? null;
            }

            $stmt = $this->pdo->prepare("
                INSERT INTO user_calculations 
                (calc_type, currency, amount, duration, step_up_pct, country_code, pdf_downloaded, referrer,
                 interest_rate, sip_amount, sip_duration, sip_step_up, swp_enabled, swp_withdrawal, swp_duration, swp_step_up,
                 final_corpus, total_invested, wealth_multiplier, goal_mode, device_type, table_viewed,
                 pdf_has_custom_name, inflation_enabled, interaction_count, preset_clicked, exit_action)
                VALUES (:calc_type, :currency, :amount, :duration, :step_up_pct, :country_code, :pdf_downloaded, :referrer,
                 :interest_rate, :sip_amount, :sip_duration, :sip_step_up, :swp_enabled, :swp_withdrawal, :swp_duration, :swp_step_up,
                 :final_corpus, :total_invested, :wealth_multiplier, :goal_mode, :device_type, :table_viewed,
                 :pdf_has_custom_name, :inflation_enabled, :interaction_count, :preset_clicked, :exit_action)
            ");

            $stmt->execute([
                ':calc_type' => $calcType,
                ':currency' => $currency,
                ':amount' => $amount,
                ':duration' => $duration,
                ':step_up_pct' => $stepUpPct,
                ':country_code' => $countryCode,
                ':pdf_downloaded' => $pdfDownloaded ? 1 : 0,
                ':referrer' => $referrer,
                ':interest_rate' => $interestRate,
                ':sip_amount' => $sipAmount,
                ':sip_duration' => $sipDuration,
                ':sip_step_up' => $sipStepUp,
                ':swp_enabled' => $swpEnabled,
                ':swp_withdrawal' => $swpWithdrawal,
                ':swp_duration' => $swpDuration,
                ':swp_step_up' => $swpStepUp,
                ':final_corpus' => $finalCorpus,
                ':total_invested' => $totalInvested,
                ':wealth_multiplier' => $wealthMultiplier,
                ':goal_mode' => $goalMode,
                ':device_type' => $deviceType,
                ':table_viewed' => $tableViewed,
                ':pdf_has_custom_name' => $pdfHasCustomName,
                ':inflation_enabled' => $inflationEnabled,
                ':interaction_count' => $interactionCount,
                ':preset_clicked' => $presetClicked ?? 'none',
                ':exit_action' => $exitAction ?? 'calc_only',
            ]);
        } catch (\Throwable $e) {
            // Silently fail to ensure user experience is never impacted by logging errors
            error_log("AnonymizedInsightLogger Error: " . $e->getMessage());
        }
    }
}
