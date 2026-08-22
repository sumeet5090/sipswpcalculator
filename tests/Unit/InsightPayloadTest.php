<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\InsightPayload;
use PHPUnit\Framework\TestCase;

class InsightPayloadTest extends TestCase
{
    public function testFromArrayWithStandardData(): void
    {
        $data = [
            'calc_type' => 'SIP',
            'amount' => 5000,
            'duration' => 15,
            'step_up_pct' => 10,
            'currency' => 'USD',
            'pdf_downloaded' => true,
            'interest_rate' => 12.5,
            'sip_amount' => 5000,
            'sip_duration' => 15,
            'sip_step_up' => 10,
            'swp_enabled' => false,
            'final_corpus' => 2500000,
            'total_invested' => 900000,
            'wealth_multiplier' => 2.78,
            'goal_mode' => 'grow',
            'device_type' => 'desktop',
            'table_viewed' => 1,
            'pdf_has_custom_name' => 0,
            'inflation_enabled' => 1,
            'interaction_count' => 3,
            'preset_clicked' => 'aggressive',
            'exit_action' => 'pdf_download',
        ];

        $payload = InsightPayload::fromArray($data);

        $this->assertSame('SIP', $payload->calcType);
        $this->assertSame(5000.0, $payload->amount);
        $this->assertSame(15, $payload->duration);
        $this->assertSame(10.0, $payload->stepUpPct);
        $this->assertSame('USD', $payload->currency);
        $this->assertTrue($payload->pdfDownloaded);
        $this->assertSame(12.5, $payload->interestRate);
        $this->assertSame(5000.0, $payload->sipAmount);
        $this->assertSame(15, $payload->sipDuration);
        $this->assertSame(10.0, $payload->sipStepUp);
        $this->assertSame(0, $payload->swpEnabled);
        $this->assertSame(2500000.0, $payload->finalCorpus);
        $this->assertSame(900000.0, $payload->totalInvested);
        $this->assertSame(2.78, $payload->wealthMultiplier);
        $this->assertSame('grow', $payload->goalMode);
        $this->assertSame('desktop', $payload->deviceType);
        $this->assertSame(1, $payload->tableViewed);
        $this->assertSame(0, $payload->pdfHasCustomName);
        $this->assertSame(1, $payload->inflationEnabled);
        $this->assertSame(3, $payload->interactionCount);
        $this->assertSame('aggressive', $payload->presetClicked);
        $this->assertSame('pdf_download', $payload->exitAction);
    }

    public function testFromArrayWithMalformedAndMissingFields(): void
    {
        $data = [
            'amount' => 'invalid_number',
            'duration' => 'ten_years',
            'swp_enabled' => 'yes',
            'pdf_downloaded' => 'true',
            'inflation_enabled' => 1,
            'table_viewed' => '0',
            'interaction_count' => -5,
            'currency' => '   gbp   ',
        ];

        $payload = InsightPayload::fromArray($data);

        $this->assertSame('SIP', $payload->calcType);
        $this->assertSame(0.0, $payload->amount);
        $this->assertSame(0, $payload->duration);
        $this->assertSame('GBP', $payload->currency);
        $this->assertTrue($payload->pdfDownloaded);
        $this->assertSame(1, $payload->swpEnabled);
        $this->assertSame(0, $payload->tableViewed);
        $this->assertSame(1, $payload->inflationEnabled);
        $this->assertSame(1, $payload->interactionCount); // Clamped to >= 1
        $this->assertNull($payload->interestRate);
        $this->assertNull($payload->finalCorpus);
        $this->assertSame('none', $payload->presetClicked);
        $this->assertSame('calc_only', $payload->exitAction);
    }

    public function testFromArrayClampsExcessivelyLongStrings(): void
    {
        $data = [
            'calc_type' => str_repeat('A', 100),
            'currency' => str_repeat('B', 50),
            'preset_clicked' => str_repeat('C', 200),
            'exit_action' => str_repeat('D', 200),
            'goal_mode' => str_repeat('E', 100),
            'device_type' => str_repeat('F', 100),
        ];

        $payload = InsightPayload::fromArray($data);

        $this->assertSame(32, mb_strlen($payload->calcType));
        $this->assertSame(10, mb_strlen($payload->currency));
        $this->assertSame(64, mb_strlen($payload->presetClicked));
        $this->assertSame(64, mb_strlen($payload->exitAction));
        $this->assertSame(32, mb_strlen($payload->goalMode ?? ''));
        $this->assertSame(32, mb_strlen($payload->deviceType ?? ''));
    }

    public function testFromArrayParsesSeoAndStudioSignals(): void
    {
        $data = [
            'calc_type' => 'SIP',
            'amount' => 10000,
            'duration' => 10,
            'landing_path' => '/swp-calculator',
            'referrer_category' => 'google_organic',
            'utm_source' => 'newsletter',
            'utm_medium' => 'email',
            'scroll_depth_pct' => 75,
            'dwell_time_seconds' => 120,
            'quick_answer_viewed' => 1,
            'faq_item_expanded' => 'faq-swp-taxation',
            'glossary_term_clicked' => 'ltcg',
            'hud_shortcut_clicked' => '#breakdown-studio',
            'active_studio_tab' => 'stress_test',
            'strategy_starter_used' => 'fire_retirement',
            'guided_wizard_completed' => 1,
            'stress_test_scenario' => '2008_subprime',
            'city_benchmark_city' => 'mumbai',
            'scenario_diff_saved' => 1,
            'csv_exported' => 1,
            'qr_modal_opened' => 1,
            'tax_waterfall_opened' => 1,
            'goal_pledge_created' => 1,
            'internal_hub_clicked' => '/step-up-sip-calculator',
            'cwv_lcp_ms' => 450,
            'cwv_cls' => 0.025,
            'cwv_inp_ms' => 65,
            'connection_speed' => '4g',
            'viewport_bucket' => 'mobile_lg',
        ];

        $payload = InsightPayload::fromArray($data);

        $this->assertSame('/swp-calculator', $payload->landingPath);
        $this->assertSame('google_organic', $payload->referrerCategory);
        $this->assertSame('newsletter', $payload->utmSource);
        $this->assertSame('email', $payload->utmMedium);
        $this->assertSame(75, $payload->scrollDepthPct);
        $this->assertSame(120, $payload->dwellTimeSeconds);
        $this->assertSame(1, $payload->quickAnswerViewed);
        $this->assertSame('faq-swp-taxation', $payload->faqItemExpanded);
        $this->assertSame('ltcg', $payload->glossaryTermClicked);
        $this->assertSame('#breakdown-studio', $payload->hudShortcutClicked);
        $this->assertSame('stress_test', $payload->activeStudioTab);
        $this->assertSame('fire_retirement', $payload->strategyStarterUsed);
        $this->assertSame(1, $payload->guidedWizardCompleted);
        $this->assertSame('2008_subprime', $payload->stressTestScenario);
        $this->assertSame('mumbai', $payload->cityBenchmarkCity);
        $this->assertSame(1, $payload->scenarioDiffSaved);
        $this->assertSame(1, $payload->csvExported);
        $this->assertSame(1, $payload->qrModalOpened);
        $this->assertSame(1, $payload->taxWaterfallOpened);
        $this->assertSame(1, $payload->goalPledgeCreated);
        $this->assertSame('/step-up-sip-calculator', $payload->internalHubClicked);
        $this->assertSame(450, $payload->cwvLcpMs);
        $this->assertSame(0.025, $payload->cwvCls);
        $this->assertSame(65, $payload->cwvInpMs);
        $this->assertSame('4g', $payload->connectionSpeed);
        $this->assertSame('mobile_lg', $payload->viewportBucket);
    }

    public function testFromArrayClampsScrollDepthAndBoundsDwellTime(): void
    {
        $data = [
            'scroll_depth_pct' => 150,
            'dwell_time_seconds' => -10,
        ];

        $payload = InsightPayload::fromArray($data);

        $this->assertSame(100, $payload->scrollDepthPct);
        $this->assertSame(0, $payload->dwellTimeSeconds);
    }
}
