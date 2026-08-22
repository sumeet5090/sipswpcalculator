<?php

declare(strict_types=1);

namespace Core;

use PDO;
use Services\TelemetryPruningService;

/**
 * Privacy-First Anonymized Insight Logger
 *
 * Logs anonymous usage statistics without storing IP addresses or PII.
 */
class AnonymizedInsightLogger
{
    private PDO $pdo;
    private TelemetryPruningService $pruningService;

    public function __construct(PDO $pdo, ?TelemetryPruningService $pruningService = null)
    {
        $this->pdo = $pdo;
        $this->pruningService = $pruningService ?? new TelemetryPruningService($pdo);
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

            $landingPath = ($payload->landingPath !== '/' || !$request)
                ? $payload->landingPath
                : (is_string($request->server('REQUEST_URI')) ? mb_substr($request->server('REQUEST_URI'), 0, 128, 'UTF-8') : '/');

            $referrerCategory = $payload->referrerCategory;
            if ($referrerCategory === 'direct' && $referrer !== null) {
                $refLower = strtolower($referrer);
                if (str_contains($refLower, 'google.')) {
                    $referrerCategory = 'google_organic';
                } elseif (str_contains($refLower, 'bing.')) {
                    $referrerCategory = 'bing_organic';
                } elseif (str_contains($refLower, 'duckduckgo.')) {
                    $referrerCategory = 'duckduckgo_organic';
                } elseif (str_contains($refLower, 'perplexity.ai') || str_contains($refLower, 'chatgpt.com') || str_contains($refLower, 'claude.ai')) {
                    $referrerCategory = 'ai_search';
                } elseif (str_contains($refLower, 'linkedin.com') || str_contains($refLower, 't.co') || str_contains($refLower, 'twitter.com') || str_contains($refLower, 'x.com') || str_contains($refLower, 'reddit.com')) {
                    $referrerCategory = 'social';
                }
            }

            $stmt = $this->pdo->prepare("
                INSERT INTO user_calculations 
                (calc_type, currency, amount, duration, step_up_pct, country_code, pdf_downloaded, referrer,
                 interest_rate, sip_amount, sip_duration, sip_step_up, swp_enabled, swp_withdrawal, swp_duration, swp_step_up,
                 final_corpus, total_invested, wealth_multiplier, goal_mode, device_type, table_viewed,
                 pdf_has_custom_name, inflation_enabled, interaction_count, preset_clicked, exit_action,
                 landing_path, referrer_category, utm_source, utm_medium, scroll_depth_pct, dwell_time_seconds,
                 quick_answer_viewed, faq_item_expanded, glossary_term_clicked, hud_shortcut_clicked,
                 active_studio_tab, strategy_starter_used, guided_wizard_completed, stress_test_scenario,
                 city_benchmark_city, scenario_diff_saved, csv_exported, qr_modal_opened, tax_waterfall_opened,
                 goal_pledge_created, internal_hub_clicked, cwv_lcp_ms, cwv_cls, cwv_inp_ms, connection_speed, viewport_bucket)
                VALUES (:calc_type, :currency, :amount, :duration, :step_up_pct, :country_code, :pdf_downloaded, :referrer,
                 :interest_rate, :sip_amount, :sip_duration, :sip_step_up, :swp_enabled, :swp_withdrawal, :swp_duration, :swp_step_up,
                 :final_corpus, :total_invested, :wealth_multiplier, :goal_mode, :device_type, :table_viewed,
                 :pdf_has_custom_name, :inflation_enabled, :interaction_count, :preset_clicked, :exit_action,
                 :landing_path, :referrer_category, :utm_source, :utm_medium, :scroll_depth_pct, :dwell_time_seconds,
                 :quick_answer_viewed, :faq_item_expanded, :glossary_term_clicked, :hud_shortcut_clicked,
                 :active_studio_tab, :strategy_starter_used, :guided_wizard_completed, :stress_test_scenario,
                 :city_benchmark_city, :scenario_diff_saved, :csv_exported, :qr_modal_opened, :tax_waterfall_opened,
                 :goal_pledge_created, :internal_hub_clicked, :cwv_lcp_ms, :cwv_cls, :cwv_inp_ms, :connection_speed, :viewport_bucket)
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
                ':landing_path' => $landingPath,
                ':referrer_category' => $referrerCategory,
                ':utm_source' => $payload->utmSource,
                ':utm_medium' => $payload->utmMedium,
                ':scroll_depth_pct' => $payload->scrollDepthPct,
                ':dwell_time_seconds' => $payload->dwellTimeSeconds,
                ':quick_answer_viewed' => $payload->quickAnswerViewed,
                ':faq_item_expanded' => $payload->faqItemExpanded,
                ':glossary_term_clicked' => $payload->glossaryTermClicked,
                ':hud_shortcut_clicked' => $payload->hudShortcutClicked,
                ':active_studio_tab' => $payload->activeStudioTab,
                ':strategy_starter_used' => $payload->strategyStarterUsed,
                ':guided_wizard_completed' => $payload->guidedWizardCompleted,
                ':stress_test_scenario' => $payload->stressTestScenario,
                ':city_benchmark_city' => $payload->cityBenchmarkCity,
                ':scenario_diff_saved' => $payload->scenarioDiffSaved,
                ':csv_exported' => $payload->csvExported,
                ':qr_modal_opened' => $payload->qrModalOpened,
                ':tax_waterfall_opened' => $payload->taxWaterfallOpened,
                ':goal_pledge_created' => $payload->goalPledgeCreated,
                ':internal_hub_clicked' => $payload->internalHubClicked,
                ':cwv_lcp_ms' => $payload->cwvLcpMs,
                ':cwv_cls' => $payload->cwvCls,
                ':cwv_inp_ms' => $payload->cwvInpMs,
                ':connection_speed' => $payload->connectionSpeed,
                ':viewport_bucket' => $payload->viewportBucket,
            ]);

            // Opportunistic telemetry retention pruning delegated to maintenance service
            $this->pruningService->opportunisticPrune(500);
        } catch (\Throwable $e) {
            // Silently fail to ensure user experience is never impacted by logging errors
            error_log("AnonymizedInsightLogger Error: " . $e->getMessage());
        }
    }
}
