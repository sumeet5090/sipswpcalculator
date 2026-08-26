<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Validates Calculator Form (calculator-form.twig, input-range-pair.twig, sip-fields.twig, swp-fields.twig)
 * DOM hierarchy, segmented tabs, steppers, AMFI category guidance, and SWP accumulation bridge.
 */
final class CalculatorFormTest extends TestCase
{
    private string $formContent;
    private string $macroContent;
    private string $sipContent;
    private string $swpContent;

    protected function setUp(): void
    {
        parent::setUp();
        $baseDir = __DIR__ . '/../../src/Views/components';
        $this->formContent = (string) file_get_contents($baseDir . '/calculator-form.twig');
        $this->macroContent = (string) file_get_contents($baseDir . '/form/input-range-pair.twig');
        $this->sipContent = (string) file_get_contents($baseDir . '/forms/sip-fields.twig');
        $this->swpContent = (string) file_get_contents($baseDir . '/forms/swp-fields.twig');
    }

    public function testCalculatorFormCoreStructureAndTabs(): void
    {
        // Form & Honeypot
        $this->assertStringContainsString('id="calculator-form"', $this->formContent);
        $this->assertStringContainsString('id="website_url"', $this->formContent);
        $this->assertStringContainsString('100% Client-Side Private', $this->formContent);

        // Segmented Tabs
        $this->assertStringContainsString('id="tab-sip"', $this->formContent);
        $this->assertStringContainsString('id="tab-swp"', $this->formContent);
        $this->assertStringContainsString('id="panel-sip"', $this->formContent);
        $this->assertStringContainsString('id="panel-swp"', $this->formContent);

        // SWP Toggle
        $this->assertStringContainsString('id="enable_swp"', $this->formContent);
        $this->assertStringContainsString('id="swp-fields"', $this->formContent);
    }

    public function testSipFieldsAndGuidanceMatrix(): void
    {
        // Core Inputs
        $this->assertStringContainsString("'id': 'sip'", $this->sipContent);
        $this->assertStringContainsString("'id': 'years'", $this->sipContent);
        $this->assertStringContainsString("'id': 'rate'", $this->sipContent);

        // AMFI Return Guidance
        $this->assertStringContainsString('id="rate-nudge-btn"', $this->sipContent);
        $this->assertStringContainsString('id="rate-nudge-popover"', $this->sipContent);
        $this->assertStringContainsString('id="rate-nudge-close"', $this->sipContent);
        $this->assertStringContainsString('class="smart-rate-option', $this->sipContent);
        $this->assertStringContainsString('data-rate="12"', $this->sipContent);
        $this->assertStringContainsString('data-rate="14"', $this->sipContent);

        // Step-Up & Appraisal Booster
        $this->assertStringContainsString("'id': 'stepup'", $this->sipContent);
        $this->assertStringContainsString('id="salary-stepup-nudge-box"', $this->sipContent);
        $this->assertStringContainsString('id="apply-10pct-stepup-btn"', $this->sipContent);
        $this->assertStringContainsString("'id': 'inflation'", $this->sipContent);
    }

    public function testSwpFieldsAndAccumulationBridge(): void
    {
        // Lifecycle Bridge Gate
        $this->assertStringContainsString("{% if calculator_type != 'swp' %}", $this->swpContent);

        // Lifecycle Bridge
        $this->assertStringContainsString('id="swp-accumulation-bridge"', $this->swpContent);
        $this->assertStringContainsString('id="bridge-matured-corpus-val"', $this->swpContent);
        $this->assertStringContainsString('id="apply-sip-to-swp-btn"', $this->swpContent);

        // SWP Core Inputs
        $this->assertStringContainsString("'id': 'swp_withdrawal'", $this->swpContent);
        $this->assertStringContainsString("'id': 'swp_years'", $this->swpContent);
        $this->assertStringContainsString("'id': 'swp_rate'", $this->swpContent);
        $this->assertStringContainsString("'id': 'swp_stepup'", $this->swpContent);
    }

    public function testSwpAccumulationBridgeConditionalRendering(): void
    {
        $renderer = new \Core\ViewRenderer(
            new \Core\ViteHelper('testing'),
            'testing',
            'https://sipswpcalculator.com',
            __DIR__ . '/../../src/Views'
        );

        $calcConfig = [
            'swp_withdrawal' => ['label' => 'Monthly Withdrawal', 'min' => 1000, 'max' => 1000000, 'slider_max' => 200000, 'step' => 1000],
            'swp_years' => ['label' => 'Withdrawal Duration', 'min' => 1, 'max' => 40, 'slider_max' => 30, 'step' => 1],
            'swp_rate' => ['label' => 'Expected Return Rate', 'min' => 1, 'max' => 30, 'slider_max' => 18, 'step' => 0.5],
            'swp_stepup' => ['label' => 'Annual Step-Up', 'min' => 0, 'max' => 25, 'slider_max' => 15, 'step' => 1],
        ];

        // Standalone SWP: Bridge MUST NOT be rendered
        $swpHtml = $renderer->render('components/forms/swp-fields', [
            'calculator_type' => 'swp',
            'calc_config' => $calcConfig,
            'swp_withdrawal' => 25000,
            'swp_years_input' => 15,
            'swp_rate' => 8,
            'swp_stepup' => 0,
        ]);
        $this->assertStringNotContainsString('id="swp-accumulation-bridge"', $swpHtml);
        $this->assertStringNotContainsString('id="apply-sip-to-swp-btn"', $swpHtml);

        // Combined Mode: Bridge MUST be rendered
        $comboHtml = $renderer->render('components/forms/swp-fields', [
            'calculator_type' => 'all',
            'calc_config' => $calcConfig,
            'swp_withdrawal' => 25000,
            'swp_years_input' => 15,
            'swp_rate' => 8,
            'swp_stepup' => 0,
        ]);
        $this->assertStringContainsString('id="swp-accumulation-bridge"', $comboHtml);
        $this->assertStringContainsString('id="apply-sip-to-swp-btn"', $comboHtml);
    }

    public function testInputRangePairMacroContracts(): void
    {
        // Stepper Buttons & Attributes
        $this->assertStringContainsString('class="stepper-btn stepper-dec', $this->macroContent);
        $this->assertStringContainsString('class="stepper-btn stepper-inc', $this->macroContent);
        $this->assertStringContainsString('data-step-action="dec"', $this->macroContent);
        $this->assertStringContainsString('data-step-action="inc"', $this->macroContent);

        // Word Badges & Telemetry
        $this->assertStringContainsString('_word_badge', $this->macroContent);
        $this->assertStringContainsString('_subtext', $this->macroContent);
        $this->assertStringContainsString('_error', $this->macroContent);

        // Preset Chips
        $this->assertStringContainsString('class="preset-chip', $this->macroContent);
        $this->assertStringContainsString('data-preset-for=', $this->macroContent);
        $this->assertStringContainsString('data-preset-val=', $this->macroContent);
    }
}
