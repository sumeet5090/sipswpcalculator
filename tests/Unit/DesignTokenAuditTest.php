<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Automated Audit Suite ensuring zero visual regressions, strict typography scaling,
 * Cool Slate neutral consistency, and institutional design tokens.
 */
final class DesignTokenAuditTest extends TestCase
{
    private string $inputCss;
    private string $stylesCss;
    private string $baseTwig;
    private string $viewsDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inputCss = (string) file_get_contents(__DIR__ . '/../../resources/css/input.css');
        $this->stylesCss = (string) file_get_contents(__DIR__ . '/../../resources/css/styles.css');
        $this->baseTwig = (string) file_get_contents(__DIR__ . '/../../src/Views/layouts/base.twig');
        $this->viewsDir = (string) realpath(__DIR__ . '/../../src/Views');
    }

    public function testInputCssDeclaresModularTypographyScale(): void
    {
        $requiredScaleTokens = [
            '--text-display-2xl',
            '--text-display-xl',
            '--text-heading-lg',
            '--text-heading-md',
            '--text-heading-sm',
            '--text-body',
            '--text-ui-sm',
            '--text-ui-xs',
            '--text-caption',
            '--text-micro',
        ];

        foreach ($requiredScaleTokens as $token) {
            $this->assertStringContainsString(
                $token,
                $this->inputCss,
                "input.css must declare modular typography token: {$token}"
            );
        }
    }

    public function testInputCssDeclares5TierElevationTokens(): void
    {
        $requiredShadowTokens = [
            '--shadow-flat',
            '--shadow-subtle',
            '--shadow-card',
            '--shadow-card-hover',
            '--shadow-floating',
            '--shadow-modal',
            '--shadow-glow-growth',
        ];

        foreach ($requiredShadowTokens as $shadowToken) {
            $this->assertStringContainsString(
                $shadowToken,
                $this->inputCss,
                "input.css must declare elevation shadow token: {$shadowToken}"
            );
        }
    }

    public function testBaseTwigPreloadsJetBrainsMono(): void
    {
        $this->assertStringContainsString(
            'family=JetBrains+Mono',
            $this->baseTwig,
            'base.twig must preload and load JetBrains Mono font family'
        );
        $this->assertStringContainsString(
            'bg-slate-50 text-slate-900',
            $this->baseTwig,
            'base.twig default body classes must strictly use Cool Slate tokens'
        );
    }

    public function testZeroGrayClassesExistInAllViews(): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->viewsDir)
        );

        $violations = [];
        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'twig') {
                $content = (string) file_get_contents($file->getPathname());
                if (preg_match('/\b(text|bg|border|ring|placeholder)-gray-[0-9]{2,3}\b/', $content, $matches)) {
                    $violations[] = $file->getFilename() . ' (' . $matches[0] . ')';
                }
            }
        }

        $this->assertEmpty(
            $violations,
            'Found prohibited gray-* classes in Twig templates (use slate-* exclusively): ' . implode(', ', $violations)
        );
    }

    public function testTabularLiningEnforcementAndFinancialMonoUtility(): void
    {
        $this->assertStringContainsString(
            '@utility font-financial-mono',
            $this->inputCss,
            'input.css must declare @utility font-financial-mono'
        );
        $this->assertStringContainsString(
            'font-variant-numeric: tabular-nums lining-nums',
            $this->stylesCss,
            'styles.css must enforce tabular lining figures'
        );
        $this->assertStringContainsString(
            'currency-affix',
            $this->stylesCss,
            'styles.css must declare .currency-affix helper class'
        );
    }

    public function testStickyTableColumnSeamRules(): void
    {
        $this->assertStringContainsString(
            '#results-table th:first-child',
            $this->inputCss,
            'input.css must declare sticky rules for frozen Year column'
        );
        $this->assertStringContainsString(
            'background-clip: padding-box',
            $this->inputCss,
            'input.css must declare background-clip: padding-box on sticky column cells to prevent sub-pixel seam bleed'
        );
    }
}
