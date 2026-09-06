<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Validates WealthPassCanvasRenderer architecture, Retina 2x offscreen Canvas rendering,
 * web font synchronization, Discreet Mode masking, and pure light-mode institutional aesthetics.
 */
final class WealthPassCanvasRendererTest extends TestCase
{
    private string $rendererCode;
    private string $shareControllerCode;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rendererCode = (string) file_get_contents(
            __DIR__ . '/../../assets/js/calculators/helpers/WealthPassCanvasRenderer.ts'
        );
        $this->shareControllerCode = (string) file_get_contents(
            __DIR__ . '/../../assets/js/calculators/controllers/ShareController.ts'
        );
    }

    public function testWealthPassCanvasRendererContract(): void
    {
        $this->assertStringContainsString('generatePassBlob(options: WealthPassOptions): Promise<Blob | null>', $this->rendererCode);
        $this->assertStringContainsString('document.fonts.ready', $this->rendererCode);
    }

    public function testWealthPassCanvasDimensionsAndDprScaling(): void
    {
        // 1200 x 630 dimensions (OpenGraph / Twitter Summary Large Card / LinkedIn standard)
        $this->assertStringContainsString('const width = 1200;', $this->rendererCode);
        $this->assertStringContainsString('const height = 630;', $this->rendererCode);
        $this->assertStringContainsString('window.devicePixelRatio', $this->rendererCode);
    }

    public function testDiscreetPrivacyModeMasking(): void
    {
        $this->assertStringContainsString('isDiscreetMode', $this->rendererCode);
        $this->assertStringContainsString('₹ **,**,***', $this->rendererCode);
    }

    public function testPureLightModeFintechColorPalette(): void
    {
        // Must use pure light base (#f8fafc) and light cards (rgba(255, 255, 255, 0.96))
        $this->assertStringContainsString('#f8fafc', $this->rendererCode);
        $this->assertStringContainsString('rgba(255, 255, 255, 0.96)', $this->rendererCode);

        // Aurora pastel glows
        $this->assertStringContainsString('rgba(16, 185, 129, 0.12)', $this->rendererCode);
        $this->assertStringContainsString('rgba(99, 102, 241, 0.08)', $this->rendererCode);

        // No dark mode surfaces
        $this->assertStringNotContainsString('#0f172a\' fillRect(0, 0, width, height)', $this->rendererCode);
    }

    public function testShareControllerIntegratesWealthPassAndWebShareApi(): void
    {
        $this->assertStringContainsString('shareWealthPass(isDiscreetMode: boolean = false)', $this->shareControllerCode);
        $this->assertStringContainsString('navigator.share', $this->shareControllerCode);
        $this->assertStringContainsString('WealthPassCanvasRenderer.generatePassBlob', $this->shareControllerCode);
    }
}
