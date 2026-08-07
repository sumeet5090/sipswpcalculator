<?php

declare(strict_types=1);

namespace Services;

/**
 * ConfigService
 * Centralized configuration loader for calculator defaults and related settings.
 */
class ConfigService
{
    private ?array $calculatorDefaults = null;
    private string $configPath;

    public function __construct(string $configPath)
    {
        $this->configPath = $configPath;
    }

    public function getCalculatorDefaults(): array
    {
        if ($this->calculatorDefaults === null) {
            if (!file_exists($this->configPath)) {
                throw new \RuntimeException("Configuration file missing at: {$this->configPath}");
            }

            $content = file_get_contents($this->configPath);
            $decoded = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                throw new \RuntimeException("Failed to parse configuration file: " . json_last_error_msg());
            }

            $this->calculatorDefaults = $decoded;
        }

        return $this->calculatorDefaults;
    }

    public function getJsonConfig(string $relativePath): array
    {
        $fullPath = __DIR__ . '/../../' . ltrim($relativePath, '/');
        if (!file_exists($fullPath)) {
            return [];
        }
        $content = file_get_contents($fullPath);
        $decoded = json_decode($content, true);
        return is_array($decoded) ? $decoded : [];
    }
}
