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

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Executes the non-blocking logging logic.
     *
     * CRITICAL: Must be called after the calculation results are displayed to the user
     * to avoid slowing down initial page load (0.7s LCP constraint).
     */
    public function logCalculation(InsightPayload $payload, ?Http\Request $request = null): void
    {
        try {
            $rawCountryCode = $request ? $request->server('HTTP_CF_IPCOUNTRY') : null;
            $countryCode = is_string($rawCountryCode) ? mb_substr(trim($rawCountryCode), 0, 10, 'UTF-8') : null;
            $rawReferrer = $request ? $request->server('HTTP_REFERER') : null;
            $referrer = is_string($rawReferrer) ? mb_substr($rawReferrer, 0, 512, 'UTF-8') : null;
            $currency = $payload->currency ?? ($request ? (string) $request->get('currency', 'INR') : 'INR');

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
                ':calc_type' => $payload->calcType,
                ':currency' => $currency,
                ':amount' => $payload->amount,
                ':duration' => $payload->duration,
                ':step_up_pct' => $payload->stepUpPct,
                ':country_code' => $countryCode,
                ':pdf_downloaded' => $payload->pdfDownloaded ? 1 : 0,
                ':referrer' => $referrer,
                ':interest_rate' => $payload->interestRate,
                ':sip_amount' => $payload->sipAmount,
                ':sip_duration' => $payload->sipDuration,
                ':sip_step_up' => $payload->sipStepUp,
                ':swp_enabled' => $payload->swpEnabled,
                ':swp_withdrawal' => $payload->swpWithdrawal,
                ':swp_duration' => $payload->swpDuration,
                ':swp_step_up' => $payload->swpStepUp,
                ':final_corpus' => $payload->finalCorpus,
                ':total_invested' => $payload->totalInvested,
                ':wealth_multiplier' => $payload->wealthMultiplier,
                ':goal_mode' => $payload->goalMode,
                ':device_type' => $payload->deviceType,
                ':table_viewed' => $payload->tableViewed,
                ':pdf_has_custom_name' => $payload->pdfHasCustomName,
                ':inflation_enabled' => $payload->inflationEnabled,
                ':interaction_count' => $payload->interactionCount,
                ':preset_clicked' => $payload->presetClicked,
                ':exit_action' => $payload->exitAction,
            ]);

            // Opportunistic telemetry retention pruning (1 in 500 requests)
            if (random_int(1, 500) === 1) {
                try {
                    $this->pdo->exec("DELETE FROM user_calculations WHERE created_at < datetime('now', '-180 days')");
                } catch (\Throwable $pe) {
                    error_log("AnonymizedInsightLogger Pruning Warning: " . $pe->getMessage());
                }
            }
        } catch (\Throwable $e) {
            // Silently fail to ensure user experience is never impacted by logging errors
            error_log("AnonymizedInsightLogger Error: " . $e->getMessage());
        }
    }
}
