<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Services\ConfigService;

class ConfigServiceTest extends TestCase
{
    private string $tempDir;
    private string $defaultsPath;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/config_service_test_' . uniqid();
        mkdir($this->tempDir);
        $this->defaultsPath = $this->tempDir . '/defaults.json';
        file_put_contents($this->defaultsPath, json_encode(['sip' => 5000, 'rate' => 12.5]));
    }

    protected function tearDown(): void
    {
        if (file_exists($this->defaultsPath)) {
            unlink($this->defaultsPath);
        }
        if (is_dir($this->tempDir)) {
            rmdir($this->tempDir);
        }
    }

    public function testGetCalculatorDefaultsReturnsParsedDataAndMemoizes(): void
    {
        $service = new ConfigService($this->defaultsPath);
        $data1 = $service->getCalculatorDefaults();

        $this->assertSame(5000, $data1['sip']);
        $this->assertSame(12.5, $data1['rate']);

        // Overwrite file to verify memory cache is used
        file_put_contents($this->defaultsPath, json_encode(['sip' => 99999]));
        $data2 = $service->getCalculatorDefaults();

        $this->assertSame(5000, $data2['sip']);
    }

    public function testGetJsonConfigReturnsEmptyArrayForMissingFile(): void
    {
        $service = new ConfigService();
        $data = $service->getJsonConfig($this->tempDir . '/non_existent.json');
        $this->assertSame([], $data);
    }

    public function testGetJsonConfigMemoizesParsedResults(): void
    {
        $filePath = $this->tempDir . '/custom.json';
        file_put_contents($filePath, json_encode(['key' => 'value1']));

        $service = new ConfigService();
        $data1 = $service->getJsonConfig($filePath);
        $this->assertSame(['key' => 'value1'], $data1);

        // Mutate file on disk
        file_put_contents($filePath, json_encode(['key' => 'value2']));
        $data2 = $service->getJsonConfig($filePath);

        $this->assertSame(['key' => 'value1'], $data2);

        unlink($filePath);
    }
}
