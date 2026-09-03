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
}
