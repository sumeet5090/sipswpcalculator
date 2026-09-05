<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Validates OdometerController and RollingOdometerView architectural integrity,
 * GPU compositor containment, zero-layout thrashing rules, and pure light-mode ambient auras.
 */
final class OdometerControllerTest extends TestCase
{
    private string $inputCss;
    private string $odometerTs;
    private string $rollingViewTs;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inputCss = (string) file_get_contents(__DIR__ . '/../../resources/css/input.css');
        $this->odometerTs = (string) file_get_contents(__DIR__ . '/../../assets/js/calculators/controllers/OdometerController.ts');
        $this->rollingViewTs = (string) file_get_contents(__DIR__ . '/../../assets/js/calculators/views/RollingOdometerView.ts');
    }

    public function testInputCssDeclaresOdometerTumblerUtilities(): void
    {
        $this->assertStringContainsString('odometer-container', $this->inputCss);
        $this->assertStringContainsString('odometer-digit-slot', $this->inputCss);
        $this->assertStringContainsString('odometer-ribbon', $this->inputCss);
        $this->assertStringContainsString('odometer-static', $this->inputCss);
    }

    public function testInputCssDeclaresPureLightModePastelAuroraAmbience(): void
    {
        $this->assertStringContainsString('.aurora-seed', $this->inputCss);
        $this->assertStringContainsString('.aurora-scale', $this->inputCss);
        $this->assertStringContainsString('.aurora-sovereign', $this->inputCss);

        // Ensure no dark mode backgrounds are used in aurora tokens
        $this->assertStringNotContainsString('bg-slate-900', $this->inputCss);
        $this->assertStringNotContainsString('bg-slate-950', $this->inputCss);
    }

    public function testOdometerControllerEliminatesSynchronousLayoutQueriesInsideRaf(): void
    {
        // Must NOT query window.getComputedStyle or clientWidth inside animation loop
        $this->assertStringNotContainsString('window.getComputedStyle(parent)', $this->odometerTs);
        $this->assertStringNotContainsString('parent.clientWidth', $this->odometerTs);
        $this->assertStringNotContainsString('el.scrollWidth', $this->odometerTs);
    }

    public function testRollingOdometerViewImplementsGpuTransformsAndA11y(): void
    {
        $this->assertStringContainsString('translate3d', $this->rollingViewTs);
        $this->assertStringContainsString('aria-label', $this->rollingViewTs);
        $this->assertStringContainsString('prefers-reduced-motion', $this->rollingViewTs);
    }

    public function testOdometerControllerUpdatesDynamicAmbientAuras(): void
    {
        $this->assertStringContainsString('updateAmbientAura', $this->odometerTs);
        $this->assertStringContainsString('aurora-sovereign', $this->odometerTs);
        $this->assertStringContainsString('aurora-scale', $this->odometerTs);
        $this->assertStringContainsString('aurora-seed', $this->odometerTs);
    }
}
