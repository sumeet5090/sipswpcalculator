<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Validates MobileErgonomicDeckController gesture navigation, directional slope locking,
 * and mobile safe-area ergonomic layout constraints.
 */
final class MobileErgonomicDeckTest extends TestCase
{
    private string $controllerCode;
    private string $inputCss;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controllerCode = (string) file_get_contents(
            __DIR__ . '/../../assets/js/calculators/controllers/MobileErgonomicDeckController.ts'
        );
        $this->inputCss = (string) file_get_contents(
            __DIR__ . '/../../resources/css/input.css'
        );
    }

    public function testMobileDeckContracts(): void
    {
        $this->assertStringContainsString('setDeckIndex(index: number): void', $this->controllerCode);
        $this->assertStringContainsString('getActiveDeckIndex(): number', $this->controllerCode);
        $this->assertStringContainsString('bindGestureSwiping(): void', $this->controllerCode);
        $this->assertStringContainsString('setActiveTab(mode: \'sip\' | \'swp\'): void', $this->controllerCode);
    }

    public function testGestureDirectionalSlopeLockMath(): void
    {
        // Must enforce directional slope lock to distinguish horizontal swipe from vertical scroll
        $this->assertStringContainsString('Math.abs(deltaX) / (Math.abs(deltaY) || 1) > 1.4', $this->controllerCode);
        $this->assertStringContainsString('navigator.vibrate', $this->controllerCode);
    }

    public function testSafeMobileErgonomicsInStylesheet(): void
    {
        // Must prevent mobile horizontal jitter without breaking position:sticky
        $this->assertStringContainsString('overflow-x: clip', $this->inputCss);
        $this->assertStringContainsString('touch-target-hig', $this->inputCss);
        $this->assertStringContainsString('hitbox-expand-44', $this->inputCss);
    }

    public function testMobileActionDockAndSheetMarkup(): void
    {
        $baseTwig = (string) file_get_contents(__DIR__ . '/../../src/Views/layouts/base.twig');
        $this->assertStringContainsString('id="mobile-action-dock"', $baseTwig);
        $this->assertStringContainsString('id="mobile-deck-corpus-val"', $baseTwig);
        $this->assertStringContainsString('id="mobile-deck-sip-btn"', $baseTwig);
        $this->assertStringContainsString('id="mobile-deck-swp-btn"', $baseTwig);
        $this->assertStringContainsString('id="mobile-deck-share-btn"', $baseTwig);
        $this->assertStringContainsString('id="mobile-actions-sheet"', $baseTwig);
        $this->assertStringContainsString('id="dock-pdf-btn"', $baseTwig);
    }

    public function testKeyboardAccessoryBarMarkupAndController(): void
    {
        $baseTwig = (string) file_get_contents(__DIR__ . '/../../src/Views/layouts/base.twig');
        $this->assertStringContainsString('id="keyboard-docked-preview"', $baseTwig);
        $this->assertStringContainsString('id="keyboard-done-btn"', $baseTwig);
        $this->assertStringContainsString('id="keyboard-prev-input"', $baseTwig);
        $this->assertStringContainsString('id="keyboard-next-input"', $baseTwig);

        $kvController = (string) file_get_contents(__DIR__ . '/../../assets/js/calculators/controllers/KeyboardViewportController.ts');
        $this->assertStringContainsString('keyboard-done-btn', $kvController);
        $this->assertStringContainsString('navigateInput', $kvController);
        $this->assertStringContainsString('scrollIntoView', $kvController);
    }

    public function testCanvasTouchScrubbingContracts(): void
    {
        $scrubberController = (string) file_get_contents(__DIR__ . '/../../assets/js/calculators/controllers/ChartScrubbingController.ts');
        $this->assertStringContainsString('bindCanvasTouchScrubbing(): void', $scrubberController);
        $this->assertStringContainsString('Math.abs(deltaX) / (Math.abs(deltaY) || 1) > 1.2', $scrubberController);
    }
}
