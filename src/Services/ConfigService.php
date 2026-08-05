<?php

declare(strict_types=1);

namespace Services;

/**
 * ConfigService
 * Centralized configuration loader for calculator defaults and related settings.
 */
class ConfigService
{
    private array $calculatorDefaults;

    public function __construct(string $configPath)
    {
        if (!file_exists($configPath)) {
            throw new \RuntimeException("Configuration file missing at: {$configPath}");
        }

        $content = file_get_contents($configPath);
        $decoded = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            throw new \RuntimeException("Failed to parse configuration file: " . json_last_error_msg());
        }

        $this->calculatorDefaults = $decoded;
    }

    public function getCalculatorDefaults(): array
    {
        return $this->calculatorDefaults;
    }
}
