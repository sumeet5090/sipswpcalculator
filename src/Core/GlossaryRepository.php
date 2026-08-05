<?php

declare(strict_types=1);

namespace Core;

/**
 * GlossaryRepository
 * Dedicated service encapsulating retrieval and sorting of financial glossary terms.
 */
class GlossaryRepository
{
    private array $terms;

    public function __construct(string $jsonPath)
    {
        if (!file_exists($jsonPath)) {
            $this->terms = [];
            return;
        }

        $jsonContent = file_get_contents($jsonPath);
        $decoded = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            error_log("Failed to parse glossary JSON: " . json_last_error_msg());
            $this->terms = [];
            return;
        }

        usort($decoded, function (array $a, array $b) {
            return strcmp($a['q'] ?? '', $b['q'] ?? '');
        });

        $this->terms = $decoded;
    }

    /**
     * Get all sorted glossary terms.
     *
     * @return array
     */
    public function getAll(): array
    {
        return $this->terms;
    }
}
