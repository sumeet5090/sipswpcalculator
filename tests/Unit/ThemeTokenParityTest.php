<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Config\ThemeConstants;
use PHPUnit\Framework\TestCase;

/**
 * Validates that theme_tokens.json, input.css @theme, and ThemeConstants.php
 * declare identical hex values for all financial color tokens.
 *
 * 21 total assertions: 6 financial domains × 3 sources + 3 font families.
 */
final class ThemeTokenParityTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $jsonTokens;

    private string $inputCss;

    protected function setUp(): void
    {
        parent::setUp();
        ThemeConstants::resetCache();
        $this->jsonTokens = $this->loadJsonTokens();
        $this->inputCss   = (string) file_get_contents(__DIR__ . '/../../resources/css/input.css');
    }

    protected function tearDown(): void
    {
        ThemeConstants::resetCache();
        parent::tearDown();
    }

    public function testFinancialGrowthColorParity(): void
    {
        /** @var array<string, mixed> $financial */
        $financial = $this->jsonTokens['colors']['financial'];
        $json      = (string) $financial['growth'];
        $php       = ThemeConstants::COLOR_FINANCIAL_GROWTH;
        $css       = $this->extractCssToken('--color-growth');

        $this->assertSame($php, $json, 'theme_tokens.json financial.growth must match ThemeConstants::COLOR_FINANCIAL_GROWTH');
        $this->assertSame($css, $php, 'input.css --color-growth must match ThemeConstants::COLOR_FINANCIAL_GROWTH');
    }

    public function testFinancialPayoutColorParity(): void
    {
        /** @var array<string, mixed> $financial */
        $financial = $this->jsonTokens['colors']['financial'];
        $json      = (string) $financial['withdrawal'];
        $php       = ThemeConstants::COLOR_FINANCIAL_WITHDRAWAL;
        $css       = $this->extractCssToken('--color-payout');

        $this->assertSame($php, $json, 'theme_tokens.json financial.withdrawal must match ThemeConstants::COLOR_FINANCIAL_WITHDRAWAL');
        $this->assertSame($css, $php, 'input.css --color-payout must match ThemeConstants::COLOR_FINANCIAL_WITHDRAWAL');
    }

    public function testFinancialPrincipalColorParity(): void
    {
        /** @var array<string, mixed> $financial */
        $financial = $this->jsonTokens['colors']['financial'];
        $json      = (string) $financial['principal'];
        $php       = ThemeConstants::COLOR_FINANCIAL_PRINCIPAL;
        $css       = $this->extractCssToken('--color-principal');

        $this->assertSame($php, $json, 'theme_tokens.json financial.principal must match ThemeConstants::COLOR_FINANCIAL_PRINCIPAL');
        $this->assertSame($css, $php, 'input.css --color-principal must match ThemeConstants::COLOR_FINANCIAL_PRINCIPAL');
    }

    public function testFinancialTaxColorParity(): void
    {
        /** @var array<string, mixed> $financial */
        $financial = $this->jsonTokens['colors']['financial'];
        $json      = (string) $financial['tax'];
        $php       = ThemeConstants::COLOR_FINANCIAL_TAX;
        $css       = $this->extractCssToken('--color-tax');

        $this->assertSame($php, $json, 'theme_tokens.json financial.tax must match ThemeConstants::COLOR_FINANCIAL_TAX');
        $this->assertSame($css, $php, 'input.css --color-tax must match ThemeConstants::COLOR_FINANCIAL_TAX');
    }

    public function testFinancialInflationColorParity(): void
    {
        /** @var array<string, mixed> $financial */
        $financial = $this->jsonTokens['colors']['financial'];
        $json      = (string) $financial['inflation'];
        $php       = ThemeConstants::COLOR_FINANCIAL_INFLATION;
        $css       = $this->extractCssToken('--color-inflation');

        $this->assertSame($php, $json, 'theme_tokens.json financial.inflation must match ThemeConstants::COLOR_FINANCIAL_INFLATION');
        $this->assertSame($css, $php, 'input.css --color-inflation must match ThemeConstants::COLOR_FINANCIAL_INFLATION');
    }

    public function testFinancialGoldColorParity(): void
    {
        /** @var array<string, mixed> $financial */
        $financial = $this->jsonTokens['colors']['financial'];
        $json      = (string) $financial['gold'];
        $php       = ThemeConstants::COLOR_FINANCIAL_GOLD;
        $css       = $this->extractCssToken('--color-gold');

        $this->assertSame($php, $json, 'theme_tokens.json financial.gold must match ThemeConstants::COLOR_FINANCIAL_GOLD');
        $this->assertSame($css, $php, 'input.css --color-gold must match ThemeConstants::COLOR_FINANCIAL_GOLD');
    }

    public function testAllFontFamiliesParity(): void
    {
        /** @var array<string, string> $fonts */
        $fonts = $this->jsonTokens['fonts'];

        $this->assertStringContainsString(
            'Inter',
            $fonts['sans'],
            'theme_tokens.json fonts.sans must reference Inter'
        );
        $this->assertStringContainsString(
            'Plus Jakarta Sans',
            $fonts['heading'],
            'theme_tokens.json fonts.heading must reference Plus Jakarta Sans'
        );
        $this->assertStringContainsString(
            'JetBrains Mono',
            $fonts['mono'],
            'theme_tokens.json fonts.mono must reference JetBrains Mono'
        );
    }

    /**
     * Load and decode theme_tokens.json with type safety.
     *
     * @return array<string, mixed>
     */
    private function loadJsonTokens(): array
    {
        $path = (string) realpath(__DIR__ . '/../../content/theme_tokens.json');
        $this->assertNotEmpty($path, 'content/theme_tokens.json must exist');

        $raw     = (string) file_get_contents($path);
        $decoded = json_decode($raw, true);
        $this->assertIsArray($decoded, 'theme_tokens.json must be valid JSON');

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }

    /**
     * Extract a hex color value from input.css @theme block by CSS custom property name.
     * Matches pattern: `--color-name: #hexvalue;` (with optional comments after).
     */
    private function extractCssToken(string $tokenName): string
    {
        $escaped = preg_quote($tokenName, '/');
        if (preg_match('/' . $escaped . ':\s*(#[0-9a-fA-F]{6,8})/', $this->inputCss, $matches)) {
            return strtolower($matches[1]);
        }

        $this->fail("CSS token '{$tokenName}' not found in input.css @theme block");
    }
}
