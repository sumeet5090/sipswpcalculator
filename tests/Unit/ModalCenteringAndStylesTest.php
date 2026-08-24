<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Validates modal centering classes, styles.css universal dialog rules, and light theme tokens.
 */
final class ModalCenteringAndStylesTest extends TestCase
{
    private string $stylesCss;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stylesCss = (string) file_get_contents(__DIR__ . '/../../resources/css/styles.css');
    }

    public function testStylesCssContainsUniversalDialogCenteringRules(): void
    {
        $this->assertStringContainsString('dialog {', $this->stylesCss);
        $this->assertStringContainsString('position: fixed;', $this->stylesCss);
        $this->assertStringContainsString('inset: 0;', $this->stylesCss);
        $this->assertStringContainsString('margin: auto;', $this->stylesCss);
        $this->assertStringContainsString('width: min(calc(100vw - 2rem), 36rem);', $this->stylesCss);
        $this->assertStringContainsString('dialog:not([open]) {', $this->stylesCss);
        $this->assertStringContainsString('display: none !important;', $this->stylesCss);
        $this->assertStringContainsString('dialog[open] {', $this->stylesCss);
        $this->assertStringContainsString('display: flex !important;', $this->stylesCss);
        $this->assertStringContainsString('dialog::backdrop', $this->stylesCss);
    }

    public function testTaxWaterfallModalHasDialogMarkup(): void
    {
        $content = (string) file_get_contents(__DIR__ . '/../../src/Views/components/tax-waterfall-modal.twig');
        $this->assertStringContainsString('id="tax-waterfall-modal"', $content);
        $this->assertStringContainsString('<dialog', $content);
    }

    public function testCommandPaletteModalHasDialogMarkup(): void
    {
        $content = (string) file_get_contents(__DIR__ . '/../../src/Views/components/command-palette.twig');
        $this->assertStringContainsString('id="command-palette-modal"', $content);
        $this->assertStringContainsString('<dialog', $content);
    }

    public function testPdfModalsHaveCenteringClasses(): void
    {
        $homeContent = (string) file_get_contents(__DIR__ . '/../../src/Views/calculators/home.twig');
        $guideContent = (string) file_get_contents(__DIR__ . '/../../src/Views/calculators/calculator-guide.twig');

        $this->assertStringContainsString('id="pdfModal"', $homeContent);
        $this->assertStringContainsString('fixed inset-0 m-auto', $homeContent);
        $this->assertStringContainsString('z-50', $homeContent);

        $this->assertStringContainsString('id="pdfModal"', $guideContent);
        $this->assertStringContainsString('fixed inset-0 m-auto', $guideContent);
        $this->assertStringContainsString('z-50', $guideContent);
    }

    public function testGoalCommitmentAndQrModalsHaveDialogMarkup(): void
    {
        $goalContent = (string) file_get_contents(__DIR__ . '/../../src/Views/components/goal-commitment-modal.twig');
        $qrContent = (string) file_get_contents(__DIR__ . '/../../src/Views/components/qr-share-modal.twig');

        $this->assertStringContainsString('id="goal-commitment-modal"', $goalContent);
        $this->assertStringContainsString('<dialog', $goalContent);

        $this->assertStringContainsString('id="qr-share-modal"', $qrContent);
        $this->assertStringContainsString('<dialog', $qrContent);
    }
}
