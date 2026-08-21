<?php

declare(strict_types=1);

namespace Services;

/**
 * ConfigServiceInterface
 * Contract for configuration loading and JSON deserialization.
 */
interface ConfigServiceInterface
{
    /**
     * Retrieve calculator defaults array from configuration store.
     *
     * @return array<string, mixed>
     */
    public function getCalculatorDefaults(): array;

    /**
     * Retrieve and decode a JSON configuration file from disk.
     *
     * @param string $path
     * @return array<string, mixed>
     */
    public function getJsonConfig(string $path): array;
}
