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
            $this->inputCss,
            'input.css must enforce tabular lining figures'
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

    public function testSummaryCardsAndMetricsGuaranteedNonTruncated(): void
    {
        $chartViz = (string) file_get_contents($this->viewsDir . '/components/chart-visualization.twig');
        $inputPair = (string) file_get_contents($this->viewsDir . '/components/form/input-range-pair.twig');

        $metricIds = ['summary-invested', 'summary-interest', 'summary-withdrawn', 'summary-corpus'];
        foreach ($metricIds as $metricId) {
            $this->assertMatchesRegularExpression(
                '/<div id="' . preg_quote($metricId, '/') . '" class="[^"]*whitespace-nowrap[^"]*"/',
                $chartViz,
                "Metric #{$metricId} must include whitespace-nowrap and must not truncate"
            );
            $this->assertDoesNotMatchRegularExpression(
                '/<div id="' . preg_quote($metricId, '/') . '" class="[^"]*\btruncate\b[^"]*"/',
                $chartViz,
                "Metric #{$metricId} must NEVER have truncate class"
            );
        }

        $this->assertDoesNotMatchRegularExpression(
            '/<span id="\{\{\s*id\s*\}\}_word_badge"[^>]*\btruncate\b/',
            $inputPair,
            'input-range-pair.twig word badges must never contain truncate class'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/<div id="\{\{\s*id\s*\}\}_subtext"[^>]*\btruncate\b/',
            $inputPair,
            'input-range-pair.twig subtext hints must never contain truncate class'
        );
    }

    public function testTableAndHudMetricsAreNonClipping(): void
    {
        $yearlyTable = (string) file_get_contents($this->viewsDir . '/components/yearly-breakdown-table.twig');
        $this->assertStringContainsString('whitespace-nowrap min-w-[5rem]', $yearlyTable);

        $this->assertMatchesRegularExpression(
            '/<span id="mini-hud-corpus" class="[^"]*whitespace-nowrap[^"]*"/',
            $this->baseTwig,
            'Mobile mini-hud corpus must be whitespace-nowrap'
        );
        $this->assertMatchesRegularExpression(
            '/<span id="mini-hud-gain" class="[^"]*whitespace-nowrap[^"]*"/',
            $this->baseTwig,
            'Mobile mini-hud gain must be whitespace-nowrap'
        );
    }

    public function testFinancialDomainColorTokensDeclared(): void
    {
        $financialColorTokens = [
            '--color-growth:',
            '--color-growth-emphasis:',
            '--color-growth-surface:',
            '--color-growth-border:',
            '--color-payout:',
            '--color-payout-emphasis:',
            '--color-payout-surface:',
            '--color-payout-border:',
            '--color-principal:',
            '--color-principal-emphasis:',
            '--color-tax:',
            '--color-tax-emphasis:',
            '--color-inflation:',
            '--color-warning:',
        ];

        foreach ($financialColorTokens as $colorToken) {
            $this->assertStringContainsString(
                $colorToken,
                $this->inputCss,
                "input.css must declare financial domain token: {$colorToken}"
            );
        }
    }

    public function testZeroArbitraryBracketFontClassesInTwigTemplates(): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->viewsDir)
        );

        $violations = [];
        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'twig') {
                $content = (string) file_get_contents($file->getPathname());
                if (preg_match_all('/\btext-\[[0-9]+(?:\.[0-9]+)?(?:px|rem)?\]/', $content, $matches)) {
                    foreach ($matches[0] as $match) {
                        $violations[] = $file->getFilename() . ' (' . $match . ')';
                    }
                }
            }
        }

        $this->assertEmpty(
            $violations,
            'Found arbitrary bracket text classes in Twig templates (use modular scale tokens e.g. text-micro, text-caption, text-ui-xs): ' . implode(', ', $violations)
        );
    }

    public function testZeroLegacyShadowClassesInTwigTemplates(): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->viewsDir)
        );

        $violations = [];
        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'twig') {
                $content = (string) file_get_contents($file->getPathname());
                if (preg_match_all('/\bshadow-(?:2xs|xs|sm|md|lg|xl|2xl)\b/', $content, $matches)) {
                    foreach ($matches[0] as $match) {
                        $violations[] = $file->getFilename() . ' (' . $match . ')';
                    }
                }
            }
        }

        $this->assertEmpty(
            $violations,
            'Found legacy Tailwind shadow classes in Twig templates (use shadow-flat, shadow-subtle, shadow-card, shadow-card-hover, shadow-floating, shadow-modal): ' . implode(', ', $violations)
        );
    }

    public function testZeroRawTailwindTextSizesInCoreCalculatorComponents(): void
    {
        // Core calculator components that MUST use semantic tokens exclusively
        $coreComponents = [
            'chart-visualization.twig',
            'calculator-form.twig',
            'input-range-pair.twig',
            'sip-fields.twig',
            'swp-fields.twig',
            'lumpsum-only-fields.twig',
            'target-corpus-fields.twig',
            'yearly-breakdown-table.twig',
            'analytical-studio.twig',
            'stress-test-simulator.twig',
            'asset-rebalancing.twig',
            'wealth-roadmap.twig',
            'city-fire-benchmark.twig',
        ];

        $violations = [];
        $files      = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->viewsDir)
        );

        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            if (
                $file->isFile()
                && $file->getExtension() === 'twig'
                && in_array($file->getFilename(), $coreComponents, true)
            ) {
                $content = (string) file_get_contents($file->getPathname());
                if (
                    preg_match_all(
                        '/\btext-(?:xs|sm|base|lg|xl|2xl|3xl|4xl|5xl|6xl|7xl|8xl|9xl)\b/',
                        $content,
                        $matches
                    )
                ) {
                    foreach ($matches[0] as $match) {
                        $violations[] = $file->getFilename() . ' (' . $match . ')';
                    }
                }
            }
        }

        $this->assertEmpty(
            $violations,
            'Core calculator components must use semantic text tokens '
            . '(text-display-*, text-heading-*, text-body, text-ui-sm, text-ui-xs, text-caption, text-micro): '
            . implode(', ', $violations)
        );
    }

    public function testZeroLegacyGlassCardInCoreComponents(): void
    {
        $coreComponents = [
            'chart-visualization.twig',
            'calculator-form.twig',
            'input-range-pair.twig',
            'sip-fields.twig',
            'swp-fields.twig',
            'lumpsum-only-fields.twig',
            'target-corpus-fields.twig',
            'yearly-breakdown-table.twig',
            'analytical-studio.twig',
            'stress-test-simulator.twig',
            'asset-rebalancing.twig',
            'wealth-roadmap.twig',
            'city-fire-benchmark.twig',
        ];

        $violations = [];
        $files      = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->viewsDir)
        );

        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            if (
                $file->isFile()
                && $file->getExtension() === 'twig'
                && in_array($file->getFilename(), $coreComponents, true)
            ) {
                $content = (string) file_get_contents($file->getPathname());
                // Match bare `glass-card` that is NOT preceded by `fintech-`
                if (preg_match_all('/(?<!fintech-)\bglass-card\b/', $content, $matches)) {
                    foreach ($matches[0] as $match) {
                        $violations[] = $file->getFilename() . ' (' . $match . ')';
                    }
                }
            }
        }

        $this->assertEmpty(
            $violations,
            'Core calculator components must use fintech-glass-card, not the deprecated glass-card: '
            . implode(', ', $violations)
        );
    }
}
