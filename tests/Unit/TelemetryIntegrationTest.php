<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Validates the DOM integration of the Emotional Telemetry Strip,
 * Wealth Horizon Pass viral share controls, and conversational narrative strip.
 */
final class TelemetryIntegrationTest extends TestCase
{
    private string $chartVizTwig;
    private string $qrShareModalTwig;
    private string $sipFieldsTwig;

    protected function setUp(): void
    {
        parent::setUp();
        $this->chartVizTwig = (string) file_get_contents(__DIR__ . '/../../src/Views/components/chart-visualization.twig');
        $this->qrShareModalTwig = (string) file_get_contents(__DIR__ . '/../../src/Views/components/qr-share-modal.twig');
        $this->sipFieldsTwig = (string) file_get_contents(__DIR__ . '/../../src/Views/components/forms/sip-fields.twig');
    }

    public function testPurchasingPowerTelemetryStripElementsExist(): void
    {
        $requiredIds = [
            'purchasing-power-telemetry-strip',
            'real-purchasing-corpus',
            'purchasing-power-anchor',
            'peace-of-mind-badge',
            'peace-of-mind-score',
            'header-share-btn',
        ];

        foreach ($requiredIds as $id) {
            $this->assertStringContainsString(
                'id="' . $id . '"',
                $this->chartVizTwig,
                "chart-visualization.twig must define required element #{$id}"
            );
        }
    }

    public function testWealthHorizonPassElementsInQrShareModal(): void
    {
        $requiredElements = [
            'id="shareWealthPassBtn"',
            'id="discreet-share-toggle"',
            'id="qr-code-canvas-container"',
            'id="qr-share-url-input"',
            'id="copy-qr-url-btn"',
        ];

        foreach ($requiredElements as $element) {
            $this->assertStringContainsString(
                $element,
                $this->qrShareModalTwig,
                "qr-share-modal.twig must contain {$element}"
            );
        }
    }

    public function testConversationalNarrativeStripElementsInSipFields(): void
    {
        $requiredNarrativeIds = [
            'conversational-narrative-strip',
            'narrative-sip',
            'narrative-years',
            'narrative-rate',
        ];

        foreach ($requiredNarrativeIds as $id) {
            $this->assertStringContainsString(
                'id="' . $id . '"',
                $this->sipFieldsTwig,
                "sip-fields.twig must define required narrative token #{$id}"
            );
        }
    }

    public function testZeroProhibitedDarkClassesInModifiedTemplates(): void
    {
        $prohibitedPatterns = [
            '/(?<!backdrop:)\bbg-slate-900\b/',
            '/(?<!backdrop:)\bbg-slate-950\b/',
            '/\bbg-gray-900\b/',
            '/\b(text|bg|border)-gray-[0-9]+\b/',
        ];

        $templates = [
            'chart-visualization.twig' => $this->chartVizTwig,
            'qr-share-modal.twig' => $this->qrShareModalTwig,
            'sip-fields.twig' => $this->sipFieldsTwig,
        ];

        foreach ($templates as $name => $content) {
            foreach ($prohibitedPatterns as $pattern) {
                $this->assertDoesNotMatchRegularExpression(
                    $pattern,
                    $content,
                    "Template {$name} violates pure light-mode/slate design system with pattern {$pattern}"
                );
            }
        }
    }
}
