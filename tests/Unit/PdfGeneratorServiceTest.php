<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\PdfTemplateInterface;
use PHPUnit\Framework\TestCase;
use Services\PdfGeneratorService;

class PdfGeneratorServiceTest extends TestCase
{
    private string $tempFontDir;
    private string $tempDompdfDir;

    protected function setUp(): void
    {
        $this->tempFontDir = sys_get_temp_dir() . '/dompdf_fonts_' . uniqid();
        $this->tempDompdfDir = sys_get_temp_dir() . '/dompdf_temp_' . uniqid();
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempFontDir);
        $this->removeDirectory($this->tempDompdfDir);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function testInitializesCustomDirectories(): void
    {
        $template = $this->createMock(PdfTemplateInterface::class);
        $service = new PdfGeneratorService($template, $this->tempFontDir, $this->tempDompdfDir);

        $this->assertSame($this->tempFontDir, $service->getFontDir());
        $this->assertSame($this->tempDompdfDir, $service->getTempDir());
        $this->assertDirectoryExists($this->tempFontDir);
        $this->assertDirectoryExists($this->tempDompdfDir);
    }

    public function testGenerateRendersHtmlIntoValidPdfBinary(): void
    {
        $template = $this->createMock(PdfTemplateInterface::class);
        $template->expects($this->once())
            ->method('render')
            ->willReturn('<!DOCTYPE html><html><body><h1>Test Report</h1><p>Hello World</p></body></html>');

        $service = new PdfGeneratorService($template, $this->tempFontDir, $this->tempDompdfDir);
        $pdf = $service->generate(['client_name' => 'John Doe']);

        $this->assertNotEmpty($pdf);
        $this->assertStringStartsWith('%PDF-', $pdf);
    }
}
