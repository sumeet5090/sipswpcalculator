<?php

declare(strict_types=1);

namespace Tests\Integration;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Validates the Utility-First Homepage layout, Analytical Studio structure,
 * Speakable and FAQ schema anchors, and DOM element ID uniqueness.
 */
final class HomepageLayoutAndAestheticsTest extends IntegrationTestCase
{
    public static function setUpBeforeClass(): void
    {
        self::startLocalServer(9012);
    }

    public static function tearDownAfterClass(): void
    {
        self::stopLocalServer();
    }

    public function testHomepageStructureAndAesthetics(): void
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:9012/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $html = (string) curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $this->assertEquals(200, $statusCode, 'Homepage must return HTTP 200 OK');
        $this->assertNotEmpty($html, 'Homepage must not return empty HTML');

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        // 1. Exactly one <h1> with proper text
        $h1s = $xpath->query('//h1');
        $this->assertEquals(1, $h1s->length, 'Homepage must have exactly one <h1>');
        $this->assertStringContainsString('SIP SWP Calculator Together', $h1s->item(0)->textContent);

        // 2. Speakable quick answer box
        $quickAnswer = $xpath->query('//*[@id="quick-answer"]');
        $this->assertEquals(1, $quickAnswer->length, '#quick-answer must exist for speakable schema');

        // 3. Strategy Starter with 4 blueprint personas
        $strategyStarter = $xpath->query('//*[@id="strategy-starter-container"]');
        $this->assertEquals(1, $strategyStarter->length, '#strategy-starter-container must exist');
        $personaBtns = $xpath->query('//button[contains(@class, "persona-btn")]');
        $this->assertGreaterThanOrEqual(4, $personaBtns->length, 'Must have at least 4 persona preset buttons');

        // 4. Analytical Studio with all 5 tabs and panels
        $studioTabs = $xpath->query('//*[@id="studio-tabs-nav"]//button[@role="tab"]');
        $this->assertEquals(5, $studioTabs->length, 'Analytical Studio must have 5 tabs');

        $requiredPanels = [
            'panel-yearly-breakdown',
            'panel-milestone-roadmap',
            'panel-stress-test',
            'panel-city-benchmark',
            'panel-asset-rebalance',
        ];

        foreach ($requiredPanels as $panelId) {
            $panel = $xpath->query("//*[@id='{$panelId}']");
            $this->assertEquals(1, $panel->length, "Panel #{$panelId} must exist in server-rendered DOM");
        }

        // 5. Research Vault and Knowledge Anchor sections
        $requiredSections = [
            'master-financial-future',
            'what-is-sip',
            'what-is-swp',
            'how-to-use-calculator',
            'math-formulas',
            'sip-calculator-formula',
            'sip-return-examples',
            'historical-sip-returns',
            'investment-risks',
            'real-life-success-story',
            'investment-comparison',
            'faq-section',
        ];

        foreach ($requiredSections as $sectionId) {
            $section = $xpath->query("//*[@id='{$sectionId}']");
            $this->assertEquals(1, $section->length, "Required SEO anchor #{$sectionId} must exist in DOM");
        }

        // 6. Floating Discovery HUD
        $hud = $xpath->query('//*[@id="floating-discovery-hud"]');
        $this->assertEquals(1, $hud->length, '#floating-discovery-hud must exist');

        // 7. Check for duplicate element IDs
        $elementsWithId = $xpath->query('//*[@id]');
        $ids = [];
        foreach ($elementsWithId as $el) {
            if ($el instanceof DOMElement) {
                $id = $el->getAttribute('id');
                if ($id !== '') {
                    $ids[] = $id;
                }
            }
        }
        $uniqueIds = array_unique($ids);
        $duplicates = array_diff_assoc($ids, $uniqueIds);

        $this->assertEmpty(
            $duplicates,
            'Homepage must not contain duplicate element IDs: [' . implode(', ', array_unique($duplicates)) . ']'
        );
    }
}
