<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\Attributes\DataProvider;

class SpecializedCalculatorsMarkupTest extends IntegrationTestCase
{
    public static function setUpBeforeClass(): void
    {
        self::startLocalServer(9007);
    }

    public static function tearDownAfterClass(): void
    {
        self::stopLocalServer();
    }

    public static function specializedCalculatorsProvider(): array
    {
        return [
            'Compound Interest' => [
                '/compound-interest-calculator',
                ['ci_principal', 'ci_principal_range', 'ci_rate', 'ci_rate_range', 'ci_years', 'ci_years_range', 'ci_frequency'],
            ],
            'CAGR' => [
                '/cagr-calculator',
                ['cagr_initial', 'cagr_initial_range', 'cagr_final', 'cagr_final_range', 'cagr_years', 'cagr_years_range'],
            ],
            'EMI' => [
                '/emi-calculator',
                ['emi_principal', 'emi_principal_range', 'emi_rate', 'emi_rate_range', 'emi_years', 'emi_years_range'],
            ],
            'Inflation' => [
                '/inflation-calculator',
                ['inf_amount', 'inf_amount_range', 'inf_rate', 'inf_rate_range', 'inf_years', 'inf_years_range'],
            ],
            'PPF' => [
                '/ppf-calculator',
                ['ppf_deposit', 'ppf_deposit_range', 'ppf_rate', 'ppf_rate_range', 'ppf_years', 'ppf_years_range', 'ppf_timing'],
            ],
            'Bank Fixed Deposit' => [
                '/fd-calculator',
                ['fd_principal', 'fd_principal_range', 'fd_rate', 'fd_rate_range', 'fd_years', 'fd_years_range', 'fd_senior', 'fd_frequency'],
            ],
        ];
    }

    /**
     * @param string[] $expectedFieldIds
     */
    #[DataProvider('specializedCalculatorsProvider')]
    public function testSpecializedCalculatorRendersAllInteractiveInputs(string $route, array $expectedFieldIds): void
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:9007' . $route);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $html = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $this->assertSame(200, $statusCode, "HTTP request did not return 200 OK for {$route}");
        $this->assertIsString($html);

        $this->assertStringContainsString('id="calculator-app"', $html, "Page {$route} missing calculator-app root container");
        $this->assertStringContainsString('id="calculator-form"', $html, "Page {$route} missing calculator-form");
        $this->assertStringContainsString('id="summary-cards-grid"', $html, "Page {$route} missing summary cards grid");
        $this->assertStringContainsString('id="summary-invested"', $html, "Page {$route} missing summary-invested");
        $this->assertStringContainsString('id="summary-interest"', $html, "Page {$route} missing summary-interest");
        $this->assertStringContainsString('id="summary-corpus"', $html, "Page {$route} missing summary-corpus");

        foreach ($expectedFieldIds as $fieldId) {
            $this->assertStringContainsString(
                "id=\"{$fieldId}\"",
                $html,
                "Page {$route} missing required interactive form input or slider: id=\"{$fieldId}\""
            );
        }
    }

    #[DataProvider('specializedCalculatorsProvider')]
    public function testSpecializedCalculatorHasNoDuplicateElementIds(string $route, array $expectedFieldIds = []): void
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:9007' . $route);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $html = (string)curl_exec($ch);

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        $elementsWithId = $xpath->query('//*[@id]');
        $this->assertNotFalse($elementsWithId);

        $ids = [];
        foreach ($elementsWithId as $element) {
            if ($element instanceof \DOMElement) {
                $id = $element->getAttribute('id');
                if ($id !== '') {
                    $ids[] = $id;
                }
            }
        }

        $uniqueIds = array_unique($ids);
        $duplicates = array_diff_assoc($ids, $uniqueIds);

        $this->assertEmpty(
            $duplicates,
            sprintf(
                "Page '%s' contains duplicate element IDs: [%s]",
                $route,
                implode(', ', array_unique($duplicates))
            )
        );
    }

    #[DataProvider('specializedCalculatorsProvider')]
    public function testSpecializedCalculatorHasValidJsonLdSchemas(string $route, array $expectedFieldIds = []): void
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:9007' . $route);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $html = (string)curl_exec($ch);

        $this->assertMatchesRegularExpression('/"@type"\s*:\s*"SoftwareApplication"/', $html, "Page {$route} missing SoftwareApplication JSON-LD schema");
        $this->assertMatchesRegularExpression('/"@type"\s*:\s*"FAQPage"/', $html, "Page {$route} missing FAQPage JSON-LD schema");
    }

    #[DataProvider('specializedCalculatorsProvider')]
    public function testSpecializedCalculatorInteractiveAttributes(string $route, array $expectedFieldIds = []): void
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:9007' . $route);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $html = (string)curl_exec($ch);

        // Assert all range sliders have min, max, step
        preg_match_all('/<input[^>]+type="range"[^>]*>/i', $html, $matches);
        $this->assertNotEmpty($matches[0], "Page {$route} has no range sliders");

        foreach ($matches[0] as $sliderHtml) {
            $this->assertMatchesRegularExpression('/min="[0-9.]+"/', $sliderHtml, "Slider missing min attribute: {$sliderHtml}");
            $this->assertMatchesRegularExpression('/max="[0-9.]+"/', $sliderHtml, "Slider missing max attribute: {$sliderHtml}");
            $this->assertMatchesRegularExpression('/step="[0-9.]+"/', $sliderHtml, "Slider missing step attribute: {$sliderHtml}");
        }

        // Assert preset chips (if present) have valid numeric data-preset-val
        preg_match_all('/data-preset-val="([^"]+)"/i', $html, $chipMatches);
        if (!empty($chipMatches[1])) {
            foreach ($chipMatches[1] as $presetVal) {
                $this->assertTrue(is_numeric($presetVal), "Invalid non-numeric preset value '{$presetVal}' in {$route}");
                $this->assertGreaterThan(0, (float)$presetVal);
            }
        }
    }
}
