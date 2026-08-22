<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Config\ThemeConstants;
use PHPUnit\Framework\TestCase;

/**
 * Validates CSS design tokens, tabular typography rules, Single Source of Truth ThemeConstants, and ThemeTokens.ts.
 */
final class TypographyAndThemeTokensTest extends TestCase
{
    private string $inputCssContent;
    private string $stylesCssContent;
    private string $themeTokensTsContent;
    private string $chartManagerTsContent;
    private string $themeTokensJsonPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inputCssContent = (string) file_get_contents(__DIR__ . '/../../resources/css/input.css');
        $this->stylesCssContent = (string) file_get_contents(__DIR__ . '/../../resources/css/styles.css');
        $this->themeTokensTsContent = (string) file_get_contents(__DIR__ . '/../../assets/js/calculators/constants/ThemeTokens.ts');
        $this->chartManagerTsContent = (string) file_get_contents(__DIR__ . '/../../assets/js/calculators/ChartManager.ts');
        $this->themeTokensJsonPath = (string) realpath(__DIR__ . '/../../content/theme_tokens.json');
    }

    public function testThemeTokensJsonFileExistsAndIsValid(): void
    {
        $this->assertFileExists($this->themeTokensJsonPath);
        $raw = (string) file_get_contents($this->themeTokensJsonPath);
        $json = json_decode($raw, true);
        $this->assertIsArray($json);
        $this->assertArrayHasKey('fonts', $json);
        $this->assertArrayHasKey('colors', $json);
        $this->assertArrayHasKey('springs', $json);
        $this->assertArrayHasKey('financial', $json['colors']);
        $this->assertArrayHasKey('chart', $json['colors']);
    }

    public function testThemeConstantsLoadsCanonicalJsonTokens(): void
    {
        ThemeConstants::resetCache();
        $tokens = ThemeConstants::getTokens();
        $this->assertArrayHasKey('fonts', $tokens);
        $this->assertArrayHasKey('colors', $tokens);
        $this->assertSame('#047857', $tokens['colors']['financial']['growth_dark']);
        $this->assertSame('#10b981', $tokens['colors']['financial']['growth']);
        $this->assertSame('#be123c', $tokens['colors']['financial']['withdrawal_dark']);
    }

    public function testTailwindV4ThemeContainsRequiredSemanticColorTokens(): void
    {
        $requiredTokens = [
            '--font-sans',
            '--font-heading',
            '--font-mono',
            '--color-financial-growth',
            '--color-financial-growth-bg',
            '--color-financial-withdrawal',
            '--color-financial-principal',
            '--color-financial-tax',
            '--color-financial-inflation',
            '--color-financial-gold',
            '--ease-spring-bounce',
            '--ease-spring-smooth',
        ];

        foreach ($requiredTokens as $token) {
            $this->assertStringContainsString(
                $token,
                $this->inputCssContent,
                "input.css must define required design token: {$token}"
            );
        }
    }

    public function testTabularNumsEnforcedAcrossNumericInputsAndTables(): void
    {
        $this->assertStringContainsString(
            'font-variant-numeric: tabular-nums lining-nums',
            $this->stylesCssContent,
            'styles.css must enforce tabular and lining numerals'
        );
        $this->assertStringContainsString(
            'font-feature-settings: "tnum" 1, "lnum" 1',
            $this->stylesCssContent,
            'styles.css must set OpenType tnum and lnum feature settings'
        );
    }

    public function testGlassCardSurfaceAdheresToPureLightModeTheme(): void
    {
        // Must NOT contain dark backgrounds
        $this->assertStringNotContainsString(
            'background-color: #0f172a',
            $this->stylesCssContent,
            'styles.css must not use inverted dark surfaces for cards'
        );
        $this->assertStringContainsString(
            '--glass-card-bg: rgba(255, 255, 255, 0.96)',
            $this->stylesCssContent,
            'styles.css must use light surface glass background token'
        );
    }

    public function testPhpThemeConstantsProvideSingleSourceOfTruth(): void
    {
        $this->assertSame('#047857', ThemeConstants::COLOR_FINANCIAL_GROWTH);
        $this->assertSame('#be123c', ThemeConstants::COLOR_FINANCIAL_WITHDRAWAL);
        $this->assertSame('#334155', ThemeConstants::COLOR_FINANCIAL_PRINCIPAL);
        $this->assertSame('#6d28d9', ThemeConstants::COLOR_FINANCIAL_TAX);
        $this->assertSame('#c2410c', ThemeConstants::COLOR_FINANCIAL_INFLATION);
        $this->assertSame('#b45309', ThemeConstants::COLOR_FINANCIAL_GOLD);
    }

    public function testTypeScriptThemeTokensProvideSingleSourceOfTruth(): void
    {
        $this->assertStringContainsString('DEFAULT_THEME_FONTS', $this->themeTokensTsContent);
        $this->assertStringContainsString('DEFAULT_THEME_COLORS', $this->themeTokensTsContent);
        $this->assertStringContainsString('getHydratedThemeTokens', $this->themeTokensTsContent);
        $this->assertStringContainsString('calculator-app-state', $this->themeTokensTsContent);
    }

    public function testChartManagerImportsFromThemeTokens(): void
    {
        $this->assertStringContainsString(
            "import { THEME_COLORS, THEME_FONTS } from './constants/ThemeTokens.ts'",
            $this->chartManagerTsContent,
            'ChartManager.ts must import THEME_COLORS and THEME_FONTS from ThemeTokens.ts'
        );
    }
}
