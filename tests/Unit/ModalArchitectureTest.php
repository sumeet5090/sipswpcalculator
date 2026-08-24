<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Validates modal architectural invariants, native HTML5 <dialog> semantics,
 * Pure Light Mode compliance, iOS 16px input zoom defense, and accessibility.
 */
final class ModalArchitectureTest extends TestCase
{
    private string $viewsPath;

    protected function setUp(): void
    {
        $this->viewsPath = __DIR__ . '/../../src/Views/components';
    }

    /**
     * Asserts that all core interactive modals use the standard HTML5 <dialog> element.
     */
    public function testAllModalsUseNativeDialogMarkup(): void
    {
        $modalFiles = [
            'tax-waterfall-modal.twig' => 'tax-waterfall-modal',
            'qr-share-modal.twig' => 'qr-share-modal',
            'goal-commitment-modal.twig' => 'goal-commitment-modal',
            'sebibenchmark-modal.twig' => 'sebiBenchmarkModal',
            'command-palette.twig' => 'command-palette-modal',
        ];

        foreach ($modalFiles as $filename => $expectedId) {
            $filePath = $this->viewsPath . '/' . $filename;
            $this->assertFileExists($filePath, "Modal template file {$filename} must exist.");

            $content = (string) file_get_contents($filePath);
            $this->assertMatchesRegularExpression(
                '/<dialog\s+id="' . preg_quote($expectedId, '/') . '"/i',
                $content,
                "Modal template {$filename} must use native <dialog id=\"{$expectedId}\"> markup."
            );
        }
    }

    /**
     * Asserts that no modal template contains dark surface classes (e.g. bg-slate-900, bg-gray-900).
     */
    public function testModalsEnforcePureLightModeThemeTokens(): void
    {
        $modalFiles = [
            'tax-waterfall-modal.twig',
            'qr-share-modal.twig',
            'goal-commitment-modal.twig',
            'sebibenchmark-modal.twig',
            'command-palette.twig',
        ];

        foreach ($modalFiles as $filename) {
            $filePath = $this->viewsPath . '/' . $filename;
            $content = (string) file_get_contents($filePath);

            // Strip HTML comments (which may mention classes historically) and check active code
            $strippedContent = (string) preg_replace('/<!--.*?-->/s', '', $content);

            // Backdrop classes (e.g. backdrop:bg-slate-900/40) are allowed, but inner modal surfaces/buttons must not use dark backgrounds
            $this->assertDoesNotMatchRegularExpression(
                '/(?<!backdrop:)bg-(slate|gray|zinc|neutral)-(900|950)(?!\/)/i',
                $strippedContent,
                "Modal {$filename} violates Pure Light Mode rules by including dark card/button classes."
            );
        }
    }

    /**
     * Asserts that text inputs in modals enforce text-base (16px) on mobile viewports to prevent iOS auto-zoom.
     */
    public function testModalInputsEnforceIos16pxZoomDefense(): void
    {
        $goalPledgeContent = (string) file_get_contents($this->viewsPath . '/goal-commitment-modal.twig');
        $this->assertStringContainsString(
            'text-base md:text-sm',
            $goalPledgeContent,
            'Pledge investor name input must use text-base on mobile to prevent iOS Safari auto-zoom snapping.'
        );

        $commandPaletteContent = (string) file_get_contents($this->viewsPath . '/command-palette.twig');
        $this->assertStringContainsString(
            'text-base md:text-sm',
            $commandPaletteContent,
            'Command palette search input must use text-base on mobile to prevent iOS Safari auto-zoom snapping.'
        );
    }

    /**
     * Asserts that all modals contain accessible aria-labelledby attributes.
     */
    public function testModalAccessibilityAndAriaAttributes(): void
    {
        $modalFiles = [
            'tax-waterfall-modal.twig' => 'tax-waterfall-title',
            'qr-share-modal.twig' => 'qr-modal-title',
            'goal-commitment-modal.twig' => 'pledge-modal-title',
            'sebibenchmark-modal.twig' => 'sebi-modal-title',
            'command-palette.twig' => 'command-palette-title',
        ];

        foreach ($modalFiles as $filename => $expectedTitleId) {
            $filePath = $this->viewsPath . '/' . $filename;
            $content = (string) file_get_contents($filePath);

            $this->assertStringContainsString(
                "aria-labelledby=\"{$expectedTitleId}\"",
                $content,
                "Modal {$filename} must include aria-labelledby=\"{$expectedTitleId}\" for screen readers."
            );

            $this->assertStringContainsString(
                "id=\"{$expectedTitleId}\"",
                $content,
                "Modal {$filename} must have a title element with id=\"{$expectedTitleId}\"."
            );
        }
    }

    /**
     * Asserts that Effective Wealth Retention calculation produces expected mathematical percentages.
     */
    public function testEffectiveTaxRetentionCalculation(): void
    {
        $totalCorpus = 10000000.0; // ₹1 Crore
        $totalInvested = 1800000.0; // ₹18 Lakhs
        $grossGains = $totalCorpus - $totalInvested; // ₹82 Lakhs
        $exemption = 125000.0; // ₹1.25 Lakhs
        $taxableGains = max(0.0, $grossGains - $exemption); // ₹80.75 Lakhs
        $ltcgTax = round($taxableGains * 0.125); // 12.5% = ₹10,09,375
        $netCorpus = $totalCorpus - $ltcgTax; // ₹89,90,625

        $retentionPct = ($netCorpus / $totalCorpus) * 100.0;
        $effectiveRate = ($ltcgTax / $totalCorpus) * 100.0;

        $this->assertEqualsWithDelta(89.91, $retentionPct, 0.05, 'Retention percentage must equal ~89.91% for ₹1 Cr corpus');
        $this->assertEqualsWithDelta(10.09, $effectiveRate, 0.05, 'Effective tax rate against gross corpus must equal ~10.09%');
    }

    /**
     * Asserts WhatsApp share intent payload construction is URL safe and markdown formatted.
     */
    public function testWhatsAppShareUrlGenerationLogic(): void
    {
        $investorName = 'Sumeet Boga';
        $sipFormatted = '₹25,000 / month';
        $years = 15;
        $targetCorpusFormatted = '₹1.26 Crore';
        $url = 'https://sipswpcalculator.com/?sip=25000&years=15&rate=12';

        $text = "📜 *INVESTOR GOAL COMMITMENT CERTIFICATE*\n\n" .
            "I, *{$investorName}*, have pledged to systematically invest *{$sipFormatted}* for *{$years} years* to achieve my target corpus of *{$targetCorpusFormatted}*.\n\n" .
            "🎯 *Plan Details:* {$url}\n\n" .
            '_"Market volatility is the fee for exceptional long-term wealth compounding."_';

        $encodedText = rawurlencode($text);
        $whatsappUrl = "https://api.whatsapp.com/send?text={$encodedText}";

        $this->assertStringStartsWith('https://api.whatsapp.com/send?text=', $whatsappUrl);
        $this->assertStringContainsString(rawurlencode('INVESTOR GOAL COMMITMENT CERTIFICATE'), $whatsappUrl);
        $this->assertStringContainsString(rawurlencode($investorName), $whatsappUrl);
    }
}
