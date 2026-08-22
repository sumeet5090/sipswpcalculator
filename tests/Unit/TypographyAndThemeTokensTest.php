<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Validates CSS design tokens, tabular typography rules, and semantic color contrast integrity.
 */
final class TypographyAndThemeTokensTest extends TestCase
{
    private string $inputCssContent;
    private string $stylesCssContent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inputCssContent = (string) file_get_contents(__DIR__ . '/../../resources/css/input.css');
        $this->stylesCssContent = (string) file_get_contents(__DIR__ . '/../../resources/css/styles.css');
    }

    public function testTailwindV4ThemeContainsRequiredSemanticColorTokens(): void
    {
        $requiredTokens = [
            '--color-financial-growth',
            '--color-financial-growth-bg',
            '--color-financial-withdrawal',
            '--color-financial-principal',
            '--color-financial-tax',
            '--color-financial-inflation',
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
}
