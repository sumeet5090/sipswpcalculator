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

    public function testStrategyStarterMobileSnapCarouselMarkup(): void
    {
        $strategyTwig = (string) file_get_contents(__DIR__ . '/../../src/Views/components/strategy-starter.twig');
        $this->assertStringContainsString('snap-x', $strategyTwig);
        $this->assertStringContainsString('snap-mandatory', $strategyTwig);
        $this->assertStringContainsString('snap-center', $strategyTwig);
        $this->assertStringContainsString('overflow-x-auto', $strategyTwig);
    }

    public function testStickyTableColumnUtilitiesAndMarkup(): void
    {
        $this->assertStringContainsString('.table-sticky-col-0', $this->inputCss);

        $breakdownTwig = (string) file_get_contents(__DIR__ . '/../../src/Views/components/yearly-breakdown-table.twig');
        $this->assertStringContainsString('table-sticky-col-0', $breakdownTwig);

        $historicalTwig = (string) file_get_contents(__DIR__ . '/../../src/Views/components/guide-historical-data.twig');
        $this->assertStringContainsString('table-sticky-col-0', $historicalTwig);

        $risksTwig = (string) file_get_contents(__DIR__ . '/../../src/Views/components/guide-risks.twig');
        $this->assertStringContainsString('table-sticky-col-0', $risksTwig);
    }

    public function testMobileActionsSheetCompleteExportSuite(): void
    {
        $baseTwig = (string) file_get_contents(__DIR__ . '/../../src/Views/layouts/base.twig');
        $this->assertStringContainsString('id="dock-glance-corpus"', $baseTwig);
        $this->assertStringContainsString('downloadCsvBtn', $baseTwig);
        $this->assertStringContainsString('saveCalculationBtn', $baseTwig);
        $this->assertStringContainsString('downloadSocialCardBtn', $baseTwig);
        $this->assertStringContainsString('shareCalcBtn', $baseTwig);
        $this->assertStringContainsString('sebiBenchmarkModal', $baseTwig);
    }

    public function testMilestoneHapticsAndStepperAcceleration(): void
    {
        $hapticCode = (string) file_get_contents(__DIR__ . '/../../assets/js/calculators/helpers/WebHapticEngine.ts');
        $this->assertStringContainsString('checkCorpusMilestone', $hapticCode);
        $this->assertStringContainsString('triggerMilestone', $hapticCode);

        $stepperCode = (string) file_get_contents(__DIR__ . '/../../assets/js/calculators/controllers/StepperController.ts');
        $this->assertStringContainsString('WebHapticEngine.triggerTick', $stepperCode);
    }

    public function testMobileSharePolymorphismAndQrCollapsible(): void
    {
        $qrModalTwig = (string) file_get_contents(__DIR__ . '/../../src/Views/components/qr-share-modal.twig');
        $this->assertStringContainsString('id="mobile-native-share-btn"', $qrModalTwig);
        $this->assertStringContainsString('id="mobile-qr-disclosure"', $qrModalTwig);
        $this->assertStringContainsString('modal-drag-handle', $qrModalTwig);

        $shareCode = (string) file_get_contents(__DIR__ . '/../../assets/js/calculators/controllers/ShareController.ts');
        $this->assertStringContainsString('tryNativeShare(): Promise<boolean>', $shareCode);
        $this->assertStringContainsString('err.name === \'AbortError\'', $shareCode);
        $this->assertStringContainsString('mobile-native-share-btn', $shareCode);
    }

    public function testBottomSheetGestureControllerContracts(): void
    {
        $gestureCode = (string) file_get_contents(
            __DIR__ . '/../../assets/js/calculators/controllers/BottomSheetGestureController.ts'
        );
        $this->assertStringContainsString('bindSheetGestures(dialog: HTMLDialogElement, handle: HTMLElement): void', $gestureCode);
        $this->assertStringContainsString('deltaY > 80', $gestureCode);
        $this->assertStringContainsString('WebHapticEngine.triggerTick', $gestureCode);
        $this->assertStringContainsString('requestAnimationFrame', $gestureCode);

        $ergoCode = (string) file_get_contents(
            __DIR__ . '/../../assets/js/calculators/subsystems/ErgonomicsSubsystem.ts'
        );
        $this->assertStringContainsString('BottomSheetGestureController', $ergoCode);
        $this->assertStringContainsString('this.bottomSheetGestureController.init()', $ergoCode);

        $baseTwig = (string) file_get_contents(__DIR__ . '/../../src/Views/layouts/base.twig');
        $this->assertStringContainsString('modal-drag-handle', $baseTwig);
    }

    public function testCityFireBenchmarkHorizontalSnapRail(): void
    {
        $cityTwig = (string) file_get_contents(__DIR__ . '/../../src/Views/components/city-fire-benchmark.twig');
        $this->assertStringContainsString('overflow-x-auto', $cityTwig);
        $this->assertStringContainsString('snap-x', $cityTwig);
        $this->assertStringContainsString('snap-mandatory', $cityTwig);
        $this->assertStringContainsString('snap-center shrink-0', $cityTwig);
    }

    public function testStressTestSimulatorHorizontalSnapRail(): void
    {
        $stressTwig = (string) file_get_contents(__DIR__ . '/../../src/Views/components/stress-test-simulator.twig');
        $this->assertStringContainsString('overflow-x-auto', $stressTwig);
        $this->assertStringContainsString('snap-x', $stressTwig);
        $this->assertStringContainsString('snap-mandatory', $stressTwig);
        $this->assertStringContainsString('snap-center shrink-0', $stressTwig);
    }

    public function testAssetRebalancingMicroRatioBars(): void
    {
        $assetTwig = (string) file_get_contents(__DIR__ . '/../../src/Views/components/asset-rebalancing.twig');
        $this->assertStringContainsString('Micro Proportional Bar', $assetTwig);
        $this->assertStringContainsString('bg-emerald-500', $assetTwig);
        $this->assertStringContainsString('bg-indigo-500', $assetTwig);
    }

    public function testMobileDiscoveryHudPlacementAndAnchors(): void
    {
        $hudTwig = (string) file_get_contents(__DIR__ . '/../../src/Views/components/floating-discovery-hud.twig');
        $this->assertStringContainsString('bottom-[74px]', $hudTwig);
        $this->assertStringContainsString('z-30', $hudTwig);
        $this->assertStringContainsString('#calculator-section', $hudTwig);
        $this->assertStringContainsString('#breakdown-studio', $hudTwig);
        $this->assertStringContainsString('#math-formulas', $hudTwig);
    }

    public function testMobileMenuScrollContainment(): void
    {
        $headerTwig = (string) file_get_contents(__DIR__ . '/../../src/Views/layouts/header.twig');
        $this->assertStringContainsString('max-h-[calc(100vh-4.5rem)]', $headerTwig);
        $this->assertStringContainsString('overflow-y-auto', $headerTwig);
        $this->assertStringContainsString('overscroll-contain', $headerTwig);
    }

    public function testStepperButtonsAndRangeInputsHaveTouchActionManipulation(): void
    {
        $this->assertStringContainsString('touch-action: manipulation', $this->inputCss);
        $this->assertStringContainsString('input[type="range"]', $this->inputCss);
        $this->assertStringContainsString('.stepper-btn', $this->inputCss);
    }

    public function testSafeAreaInsetBottomClampingOnMobileDock(): void
    {
        $baseTwig = (string) file_get_contents(__DIR__ . '/../../src/Views/layouts/base.twig');
        $this->assertStringContainsString('pb-[max(0.625rem,env(safe-area-inset-bottom))]', $baseTwig);
    }

    public function testKeyboardControllerCoordinatesFloatingHudAndFocusSelection(): void
    {
        $keyboardCode = (string) file_get_contents(__DIR__ . '/../../assets/js/calculators/controllers/KeyboardViewportController.ts');
        $this->assertStringContainsString('floating-discovery-hud', $keyboardCode);
        $this->assertStringContainsString('pointer-events-none', $keyboardCode);
        $this->assertStringContainsString('input.select()', $keyboardCode);
    }

    public function testTaxWaterfallModalHasDragHandle(): void
    {
        $taxModalTwig = (string) file_get_contents(__DIR__ . '/../../src/Views/components/tax-waterfall-modal.twig');
        $this->assertStringContainsString('modal-drag-handle', $taxModalTwig);
        $this->assertStringContainsString('bg-slate-300 rounded-full mx-auto mb-2 sm:hidden', $taxModalTwig);
    }

    public function testTableScrollBoundaryMaskUtilitiesAndMarkup(): void
    {
        $this->assertStringContainsString('.table-scroll-mask-end', $this->inputCss);
        $this->assertStringContainsString('mask-image: linear-gradient(to right, black 85%, transparent 100%)', $this->inputCss);

        $historicalTwig = (string) file_get_contents(__DIR__ . '/../../src/Views/components/guide-historical-data.twig');
        $this->assertStringContainsString('table-scroll-mask-end', $historicalTwig);

        $risksTwig = (string) file_get_contents(__DIR__ . '/../../src/Views/components/guide-risks.twig');
        $this->assertStringContainsString('table-scroll-mask-end', $risksTwig);
    }

    public function testChartScrubbingElevatesHudOnTouchInteraction(): void
    {
        $scrubberController = (string) file_get_contents(__DIR__ . '/../../assets/js/calculators/controllers/ChartScrubbingController.ts');
        $this->assertStringContainsString('chart-inspection-hud', $scrubberController);
        $this->assertStringContainsString('ring-emerald-400/60', $scrubberController);
        $this->assertStringContainsString('touchcancel', $scrubberController);
    }
}
