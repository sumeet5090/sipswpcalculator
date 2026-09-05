<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Validates ArchetypeInjectorController architecture, preset parameters,
 * DOM parity in sip-fields.twig, WAI-ARIA accessibility, and pure light-mode styling.
 */
final class ArchetypeInjectorTest extends TestCase
{
    private string $controllerCode;
    private string $sipFieldsTwig;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controllerCode = (string) file_get_contents(
            __DIR__ . '/../../assets/js/calculators/controllers/ArchetypeInjectorController.ts'
        );
        $this->sipFieldsTwig = (string) file_get_contents(
            __DIR__ . '/../../src/Views/components/forms/sip-fields.twig'
        );
    }

    public function testSipFieldsTwigContainsArchetypeToolbar(): void
    {
        $this->assertStringContainsString('role="toolbar"', $this->sipFieldsTwig);
        $this->assertStringContainsString('aria-label="Investment Archetypes"', $this->sipFieldsTwig);
        $this->assertStringContainsString('data-archetype="fresher"', $this->sipFieldsTwig);
        $this->assertStringContainsString('data-archetype="lead"', $this->sipFieldsTwig);
        $this->assertStringContainsString('data-archetype="fire"', $this->sipFieldsTwig);
        $this->assertStringContainsString('data-archetype="legacy"', $this->sipFieldsTwig);
    }

    public function testArchetypeChipsUsePureLightModeThemeClasses(): void
    {
        // Must use light mode palette
        $this->assertStringContainsString('bg-slate-50', $this->sipFieldsTwig);
        $this->assertStringContainsString('border-slate-200', $this->sipFieldsTwig);
        $this->assertStringContainsString('text-slate-700', $this->sipFieldsTwig);

        // Under no circumstances should dark mode backgrounds exist
        $this->assertStringNotContainsString('bg-slate-900', $this->sipFieldsTwig);
        $this->assertStringNotContainsString('bg-gray-900', $this->sipFieldsTwig);
    }

    public function testArchetypeControllerContracts(): void
    {
        $this->assertStringContainsString('applyArchetype(archetypeId: string): void', $this->controllerCode);
        $this->assertStringContainsString('getArchetypes(): Record<string, ArchetypePreset>', $this->controllerCode);
        $this->assertStringContainsString('A11yAnnouncer.announce', $this->controllerCode);
        $this->assertStringContainsString('navigator.vibrate', $this->controllerCode);
    }

    public function testArchetypePresetMathematicalParity(): void
    {
        // Fresher: 10k, 10y, 14%
        $this->assertStringContainsString("'fresher'", $this->controllerCode);
        $this->assertStringContainsString('sip: 10000', $this->controllerCode);
        $this->assertStringContainsString('years: 10', $this->controllerCode);

        // Lead: 50k, 15y, 12.5%
        $this->assertStringContainsString("'lead'", $this->controllerCode);
        $this->assertStringContainsString('sip: 50000', $this->controllerCode);
        $this->assertStringContainsString('years: 15', $this->controllerCode);

        // FIRE: 1.5L, 12y, 13%
        $this->assertStringContainsString("'fire'", $this->controllerCode);
        $this->assertStringContainsString('sip: 150000', $this->controllerCode);

        // Legacy: 25k, 20y, 12%
        $this->assertStringContainsString("'legacy'", $this->controllerCode);
        $this->assertStringContainsString('sip: 25000', $this->controllerCode);
    }
}
