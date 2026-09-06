<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Validates the unified StrategyBlueprintController architecture, preset parameters,
 * DOM parity in strategy-starter.twig and sip-fields.twig, WAI-ARIA accessibility, and pure light-mode styling.
 */
final class ArchetypeInjectorTest extends TestCase
{
    private string $controllerCode;
    private string $strategyStarterTwig;
    private string $sipFieldsTwig;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controllerCode = (string) file_get_contents(
            __DIR__ . '/../../assets/js/calculators/controllers/StrategyBlueprintController.ts'
        );
        $this->strategyStarterTwig = (string) file_get_contents(
            __DIR__ . '/../../src/Views/components/strategy-starter.twig'
        );
        $this->sipFieldsTwig = (string) file_get_contents(
            __DIR__ . '/../../src/Views/components/forms/sip-fields.twig'
        );
    }

    public function testStrategyStarterTwigContainsBlueprintToolbar(): void
    {
        $this->assertStringContainsString('role="toolbar"', $this->strategyStarterTwig);
        $this->assertStringContainsString('aria-label="Strategy Blueprints"', $this->strategyStarterTwig);
        $this->assertStringContainsString('data-persona="first_crore"', $this->strategyStarterTwig);
        $this->assertStringContainsString('data-persona="fire_retirement"', $this->strategyStarterTwig);
        $this->assertStringContainsString('data-persona="child_education"', $this->strategyStarterTwig);
        $this->assertStringContainsString('data-persona="capital_preservation"', $this->strategyStarterTwig);
        $this->assertStringContainsString('aria-pressed="false"', $this->strategyStarterTwig);
    }

    public function testBlueprintButtonsUsePureLightModeThemeClasses(): void
    {
        // Must use light mode palette
        $this->assertStringContainsString('bg-white/95', $this->strategyStarterTwig);
        $this->assertStringContainsString('border-slate-200', $this->strategyStarterTwig);
        $this->assertStringContainsString('text-slate-800', $this->strategyStarterTwig);

        // Under no circumstances should dark mode backgrounds exist
        $this->assertStringNotContainsString('bg-slate-900', $this->strategyStarterTwig);
        $this->assertStringNotContainsString('bg-gray-900', $this->strategyStarterTwig);
    }

    public function testSipFieldsContainsDynamicNarrativeStrategyBadgeWithoutDuplicateChips(): void
    {
        $this->assertStringContainsString('id="conversational-narrative-strip"', $this->sipFieldsTwig);
        $this->assertStringContainsString('id="narrative-strategy-badge"', $this->sipFieldsTwig);
        $this->assertStringNotContainsString('class="archetype-chip', $this->sipFieldsTwig);
    }

    public function testStrategyBlueprintControllerContracts(): void
    {
        $this->assertStringContainsString('applyBlueprint(personaId: string): void', $this->controllerCode);
        $this->assertStringContainsString('resetActiveState(): void', $this->controllerCode);
        $this->assertStringContainsString('syncWithInputs(inputs: InvestmentInputs): void', $this->controllerCode);
        $this->assertStringContainsString('getBlueprints(): Record<string, StrategyBlueprintPreset>', $this->controllerCode);
        $this->assertStringContainsString('A11yAnnouncer.announce', $this->controllerCode);
        $this->assertStringContainsString('navigator.vibrate', $this->controllerCode);
    }

    public function testStrategyBlueprintMathematicalParity(): void
    {
        // First ₹1 Crore: 25k, 9y, 12% CAGR, 10% stepup
        $this->assertStringContainsString("'first_crore'", $this->controllerCode);
        $this->assertStringContainsString('sip: 25000', $this->controllerCode);
        $this->assertStringContainsString('years: 9', $this->controllerCode);
        $this->assertStringContainsString('rate: 12', $this->controllerCode);

        // FIRE Early Retirement: 50k, 15y, 12%, swp: 120000, 25y
        $this->assertStringContainsString("'fire_retirement'", $this->controllerCode);
        $this->assertStringContainsString('sip: 50000', $this->controllerCode);
        $this->assertStringContainsString('years: 15', $this->controllerCode);
        $this->assertStringContainsString('swp: 120000', $this->controllerCode);

        // Child Higher Education: 15k, 18y, 12%, lumpsum: 100000
        $this->assertStringContainsString("'child_education'", $this->controllerCode);
        $this->assertStringContainsString('sip: 15000', $this->controllerCode);
        $this->assertStringContainsString('years: 18', $this->controllerCode);

        // Senior Capital Preservation: lumpsum: 5000000, swp: 35000, 20y
        $this->assertStringContainsString("'capital_preservation'", $this->controllerCode);
        $this->assertStringContainsString('lumpsum: 5000000', $this->controllerCode);
        $this->assertStringContainsString('swp: 35000', $this->controllerCode);
    }
}
