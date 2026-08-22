<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\AnonymizedInsightLogger;
use Core\Http\Request;
use Core\InsightPayload;
use Core\InsightRepository;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * SeoTelemetryIntegrationTest
 * Validates SQLite migration 005, payload translation, logging, and repository aggregation of SEO metrics.
 */
class SeoTelemetryIntegrationTest extends TestCase
{
    private PDO $pdo;
    private AnonymizedInsightLogger $logger;
    private InsightRepository $repository;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // Run initial migration
        $migration1 = require __DIR__ . '/../../database/migrations/20260701_001_initial_schema.php';
        $migration1->up($this->pdo, true);

        // Run migration 002
        $migration2 = require __DIR__ . '/../../database/migrations/20260714_002_add_country_code.php';
        $migration2->up($this->pdo, true);

        // Run migration 003
        $migration3 = require __DIR__ . '/../../database/migrations/20260728_003_add_deep_privacy_telemetry.php';
        $migration3->up($this->pdo, true);

        // Run migration 004
        $migration4 = require __DIR__ . '/../../database/migrations/20260728_004_add_advanced_privacy_signals.php';
        $migration4->up($this->pdo, true);

        // Run migration 005
        $migration5 = require __DIR__ . '/../../database/migrations/20260823_005_add_seo_and_studio_telemetry.php';
        $migration5->up($this->pdo, true);

        $this->logger = new AnonymizedInsightLogger($this->pdo);
        $this->repository = new InsightRepository($this->pdo);
    }

    public function testMigrationAndTelemetryPipelineRecordsSeoSignals(): void
    {
        $payload = InsightPayload::fromArray([
            'calc_type' => 'SIP',
            'amount' => 50000,
            'duration' => 20,
            'step_up_pct' => 10,
            'currency' => 'INR',
            'landing_path' => '/',
            'referrer_category' => 'google_organic',
            'utm_source' => 'twitter',
            'utm_medium' => 'social',
            'scroll_depth_pct' => 100,
            'dwell_time_seconds' => 180,
            'quick_answer_viewed' => 1,
            'faq_item_expanded' => 'faq-sip-compounding',
            'glossary_term_clicked' => 'xirr',
            'hud_shortcut_clicked' => '#faq',
            'active_studio_tab' => 'milestone_roadmap',
            'strategy_starter_used' => 'crorepati_blueprint',
            'guided_wizard_completed' => 1,
            'stress_test_scenario' => 'dotcom_crash',
            'city_benchmark_city' => 'pune',
            'scenario_diff_saved' => 1,
            'csv_exported' => 1,
            'qr_modal_opened' => 1,
            'tax_waterfall_opened' => 1,
            'goal_pledge_created' => 1,
            'internal_hub_clicked' => '/crorepati-calculator',
            'cwv_lcp_ms' => 410,
            'cwv_cls' => 0.005,
            'cwv_inp_ms' => 55,
            'connection_speed' => '4g',
            'viewport_bucket' => 'desktop',
        ]);

        $request = new Request([], [], [
            'HTTP_CF_IPCOUNTRY' => 'IN',
            'HTTP_REFERER' => 'https://www.google.com/',
            'REQUEST_URI' => '/',
        ]);

        $this->logger->logCalculation($payload, $request);

        $data = $this->repository->getDashboardData([
            'label' => '30 Days',
            'interval' => '-30 days',
            'unit' => 'day',
            'cte_start' => '-29 days',
        ]);

        $this->assertSame(1, $data['totalCalculations']);
        $this->assertEquals(100.0, $data['avgScrollDepth']);
        $this->assertEquals(180.0, $data['avgDwellTime']);
        $this->assertNotEmpty($data['referrerDist']);
        $this->assertSame('google_organic', $data['referrerDist'][0]['ref']);
        $this->assertSame('milestone_roadmap', $data['studioTabDist'][0]['tab']);
        $this->assertSame('crorepati_blueprint', $data['strategyStarterDist'][0]['preset']);
    }

    public function testAutoReferrerResolutionFromHeaders(): void
    {
        $payload = InsightPayload::fromArray([
            'calc_type' => 'SIP',
            'amount' => 15000,
            'duration' => 10,
            'referrer_category' => 'direct',
        ]);

        $request = new Request([], [], [
            'HTTP_REFERER' => 'https://www.bing.com/search?q=swp+calculator',
        ]);

        $this->logger->logCalculation($payload, $request);

        $stmt = $this->pdo->query("SELECT referrer_category FROM user_calculations LIMIT 1");
        $category = $stmt->fetchColumn();

        $this->assertSame('bing_organic', $category);
    }
}
