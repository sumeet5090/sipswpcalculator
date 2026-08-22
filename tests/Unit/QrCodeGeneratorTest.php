<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Validates QR code generator matrix encoding and layout geometry.
 */
final class QrCodeGeneratorTest extends TestCase
{
    public function testQrCodeMatrixGenerationValidFormat(): void
    {
        $nodeScript = __DIR__ . '/../run_js_calc.js';
        $testUrl = 'https://sipswpcalculator.com/sip-calculator?sip=25000&years=15&rate=12&stepup=10&lumpsum=0';
        $payload = json_encode([
            'action' => 'generate_qr_test',
            'text' => $testUrl
        ], JSON_THROW_ON_ERROR);

        $cmd = 'node ' . escapeshellarg($nodeScript) . ' ' . escapeshellarg($payload);
        $output = shell_exec($cmd);
        $result = json_decode((string) $output, true, 512, JSON_THROW_ON_ERROR);

        $this->assertNotNull($result, 'Node execution result must be valid JSON');
        $this->assertTrue($result['success']);
        $this->assertTrue($result['isSquare'], 'QR Code matrix must be a perfect square');
        $this->assertGreaterThanOrEqual(21, $result['size'], 'QR Code matrix must have minimum Version 1 size (21x21)');
        $this->assertTrue($result['hasTopLeftFinder'], 'QR Code matrix must contain valid top-left 7x7 finder pattern');
    }

    public function testQrModalContainsAriaAttributesAndCanvasContainer(): void
    {
        $modalTwigPath = __DIR__ . '/../../src/Views/components/qr-share-modal.twig';
        $this->assertFileExists($modalTwigPath);
        $content = file_get_contents($modalTwigPath);
        $this->assertIsString($content);

        $this->assertStringContainsString('id="qr-share-modal"', $content);
        $this->assertStringContainsString('id="qr-code-canvas-container"', $content);
        $this->assertStringContainsString('id="qr-share-url-input"', $content);
        $this->assertStringContainsString('id="copy-qr-url-btn"', $content);
        $this->assertStringContainsString('role="dialog"', $content);
        $this->assertStringContainsString('aria-modal="true"', $content);
    }
}
