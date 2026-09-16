<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\ViteHelper;
use PHPUnit\Framework\TestCase;

class ViteHelperTest extends TestCase
{
    private string $tempManifestPath;
    private string $originalErrorLog;

    protected function setUp(): void
    {
        $this->originalErrorLog = (string) ini_get('error_log');
        ini_set('error_log', '/dev/null');

        $this->tempManifestPath = sys_get_temp_dir() . '/test_manifest_' . uniqid() . '.json';
        $mockManifest = [
            'resources/js/app.ts' => [
                'file' => 'assets/app-12345678.js',
                'name' => 'app',
                'src' => 'resources/js/app.ts',
                'isEntry' => true,
                'css' => [
                    'assets/app-87654321.css',
                ],
            ],
            'resources/css/custom.css' => [
                'file' => 'assets/custom-abcdef12.css',
                'src' => 'resources/css/custom.css',
            ],
        ];

        file_put_contents($this->tempManifestPath, (string) json_encode($mockManifest, JSON_PRETTY_PRINT));
    }

    protected function tearDown(): void
    {
        ini_set('error_log', $this->originalErrorLog);

        if (file_exists($this->tempManifestPath)) {
            unlink($this->tempManifestPath);
        }
    }

    public function testAssetResolvesHashedJsFromManifestInProduction(): void
    {
        $helper = new ViteHelper('production', '127.0.0.1', 5173, $this->tempManifestPath);

        $this->assertEquals('/dist/assets/app-12345678.js', $helper->asset('resources/js/app.ts'));
    }

    public function testAssetResolvesWithLeadingSlashOrShorthandName(): void
    {
        $helper = new ViteHelper('production', '127.0.0.1', 5173, $this->tempManifestPath);

        // Leading slash normalization
        $this->assertEquals('/dist/assets/app-12345678.js', $helper->asset('/resources/js/app.ts'));

        // Shorthand name match
        $this->assertEquals('/dist/assets/app-12345678.js', $helper->asset('app'));
    }

    public function testCssGeneratesLinkTagFromManifest(): void
    {
        $helper = new ViteHelper('production', '127.0.0.1', 5173, $this->tempManifestPath);

        $expectedHtml = '<link rel="stylesheet" href="/dist/assets/app-87654321.css">';
        $this->assertEquals($expectedHtml, $helper->css('resources/js/app.ts'));
        $this->assertEquals($expectedHtml, $helper->css('/resources/js/app.ts'));
        $this->assertEquals($expectedHtml, $helper->css('app'));
    }

    public function testCssResolvesWhenDirectCssPathIsPassed(): void
    {
        $helper = new ViteHelper('production', '127.0.0.1', 5173, $this->tempManifestPath);

        // Direct CSS path that is bundled inside an entry's 'css' array
        $expectedHtml = '<link rel="stylesheet" href="/dist/assets/app-87654321.css">';
        $this->assertEquals($expectedHtml, $helper->css('resources/css/input.css'));

        // Standalone CSS entry in manifest
        $expectedCustomHtml = '<link rel="stylesheet" href="/dist/assets/custom-abcdef12.css">';
        $this->assertEquals($expectedCustomHtml, $helper->css('resources/css/custom.css'));
    }

    public function testJsGeneratesModuleScriptTagFromManifest(): void
    {
        $helper = new ViteHelper('production', '127.0.0.1', 5173, $this->tempManifestPath);

        $expectedHtml = '<script type="module" src="/dist/assets/app-12345678.js"></script>';
        $this->assertEquals($expectedHtml, $helper->js('resources/js/app.ts'));
    }

    public function testProductionMissingManifestEntryReturnsEmptyString(): void
    {
        $tempLog = (string) tempnam(sys_get_temp_dir(), 'vite_err_');
        ini_set('error_log', $tempLog);

        $helper = new ViteHelper('production', '127.0.0.1', 5173, $this->tempManifestPath);

        $this->assertEquals('', $helper->asset('nonexistent/entry.ts'));
        $this->assertEquals('', $helper->css('nonexistent/entry.ts'));

        $logOutput = file_exists($tempLog) ? (string) file_get_contents($tempLog) : '';
        $this->assertStringContainsString('ViteHelper Warning: Manifest entry missing', $logOutput);

        if (file_exists($tempLog)) {
            unlink($tempLog);
        }
    }

    public function testProductionMissingManifestFileReturnsEmptyString(): void
    {
        $tempLog = (string) tempnam(sys_get_temp_dir(), 'vite_err_');
        ini_set('error_log', $tempLog);

        $nonExistentPath = sys_get_temp_dir() . '/missing_manifest_' . uniqid() . '.json';
        $helper = new ViteHelper('production', '127.0.0.1', 5173, $nonExistentPath);

        $this->assertEquals('', $helper->asset('resources/js/app.ts'));
        $this->assertEquals('', $helper->css('resources/js/app.ts'));

        $logOutput = file_exists($tempLog) ? (string) file_get_contents($tempLog) : '';
        $this->assertStringContainsString('ViteHelper Warning: Manifest entry missing', $logOutput);

        if (file_exists($tempLog)) {
            unlink($tempLog);
        }
    }

    public function testTestingEnvironmentDisablesViteClientTag(): void
    {
        $helper = new ViteHelper('testing', '127.0.0.1', 5173, $this->tempManifestPath);

        $this->assertEquals('', $helper->client());
    }

    public function testDevelopmentModeFallsBackSafelyWhenDevServerOffline(): void
    {
        // Port 59999 is typically unused, simulating offline dev server
        $helper = new ViteHelper('development', '127.0.0.1', 59999, $this->tempManifestPath);

        // Dev server is offline, so it resolves from compiled manifest
        $this->assertEquals('/dist/assets/app-12345678.js', $helper->asset('resources/js/app.ts'));
        $this->assertEquals('<link rel="stylesheet" href="/dist/assets/app-87654321.css">', $helper->css('resources/js/app.ts'));
    }

    public function testHandlesCorruptedOrInvalidJsonManifest(): void
    {
        $tempLog = (string) tempnam(sys_get_temp_dir(), 'vite_err_');
        ini_set('error_log', $tempLog);

        $corruptedPath = sys_get_temp_dir() . '/corrupted_manifest_' . uniqid() . '.json';
        file_put_contents($corruptedPath, '{ invalid json content !!!');

        $helper = new ViteHelper('production', '127.0.0.1', 5173, $corruptedPath);

        $this->assertEquals('', $helper->asset('resources/js/app.ts'));
        $this->assertEquals('', $helper->css('resources/js/app.ts'));

        $logOutput = file_exists($tempLog) ? (string) file_get_contents($tempLog) : '';
        $this->assertStringContainsString('ViteHelper Warning: Manifest entry missing', $logOutput);

        if (file_exists($corruptedPath)) {
            unlink($corruptedPath);
        }
        if (file_exists($tempLog)) {
            unlink($tempLog);
        }
    }

    public function testHandlesEmptyManifestArray(): void
    {
        $tempLog = (string) tempnam(sys_get_temp_dir(), 'vite_err_');
        ini_set('error_log', $tempLog);

        $emptyPath = sys_get_temp_dir() . '/empty_manifest_' . uniqid() . '.json';
        file_put_contents($emptyPath, json_encode([]));

        $helper = new ViteHelper('production', '127.0.0.1', 5173, $emptyPath);

        $this->assertEquals('', $helper->asset('resources/js/app.ts'));
        $this->assertEquals('', $helper->css('resources/js/app.ts'));

        $logOutput = file_exists($tempLog) ? (string) file_get_contents($tempLog) : '';
        $this->assertStringContainsString('ViteHelper Warning: Manifest entry missing', $logOutput);

        if (file_exists($emptyPath)) {
            unlink($emptyPath);
        }
        if (file_exists($tempLog)) {
            unlink($tempLog);
        }
    }

    public function testHandlesMultipleLeadingSlashesAndWhitespace(): void
    {
        $helper = new ViteHelper('production', '127.0.0.1', 5173, $this->tempManifestPath);

        $this->assertEquals('/dist/assets/app-12345678.js', $helper->asset('   ///resources/js/app.ts   '));
        $this->assertEquals('<link rel="stylesheet" href="/dist/assets/app-87654321.css">', $helper->css('  app  '));
    }
}
