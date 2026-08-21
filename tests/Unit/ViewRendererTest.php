<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\CurrencyFormatterInterface;
use Core\Twig\AppTwigExtension;
use Core\ViewRenderer;
use Core\ViteHelper;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ViewRendererTest extends TestCase
{
    private string $tempViewsDir;

    protected function setUp(): void
    {
        $this->tempViewsDir = sys_get_temp_dir() . '/views_test_' . uniqid();
        mkdir($this->tempViewsDir);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempViewsDir);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    private function createViewRenderer(): ViewRenderer
    {
        $viteHelper = $this->createStub(ViteHelper::class);
        $currencyFormatter = $this->createStub(CurrencyFormatterInterface::class);

        return new ViewRenderer(
            $viteHelper,
            'development',
            'https://sipswpcalculator.com',
            $this->tempViewsDir,
            null,
            $currencyFormatter
        );
    }

    public function testThrowsExceptionWhenTemplateFileMissing(): void
    {
        $renderer = $this->createViewRenderer();

        $this->expectException(\Core\Exceptions\NotFoundException::class);
        $renderer->getTemplateModifiedDate('nonexistent_view');
    }

    public function testRenderThrowsNotFoundExceptionWhenTemplateMissing(): void
    {
        $renderer = $this->createViewRenderer();

        $this->expectException(\Core\Exceptions\NotFoundException::class);
        $renderer->render('nonexistent_template');
    }

    public function testNormalizesTwigExtensionAutomatically(): void
    {
        $renderer = $this->createViewRenderer();

        file_put_contents($this->tempViewsDir . '/test_view.twig', 'Test content');

        $date1 = $renderer->getTemplateModifiedDate('test_view');
        $date2 = $renderer->getTemplateModifiedDate('test_view.twig');

        $this->assertEquals($date1, $date2);
        $this->assertEquals(date('Y-m-d'), $date1);
    }
}
