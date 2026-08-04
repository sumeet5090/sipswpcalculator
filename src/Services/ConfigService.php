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

    public function __construct(?string $customConfigPath = null)
    {
        $jsonPath = $customConfigPath ?? __DIR__ . '/../../content/calculator_defaults.json';
        if (!file_exists($jsonPath)) {
            throw new \Exception("Configuration file missing at: {$jsonPath}");
        }

        $content = file_get_contents($jsonPath);
        $decoded = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            throw new \Exception("Failed to parse configuration file: " . json_last_error_msg());
        }

        $this->calculatorDefaults = $decoded;
    }

    public function getCalculatorDefaults(): array
    {
        return $this->calculatorDefaults;
    }
}
