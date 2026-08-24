<?php

declare(strict_types=1);

namespace Core;

use Services\ConfigServiceInterface;

/**
 * GlossaryRepository
 * Dedicated service encapsulating retrieval and sorting of financial glossary terms.
 */
class GlossaryRepository
{
    private ?array $terms = null;
    private string $jsonPath;
    private ConfigServiceInterface $configService;

    public function __construct(string $jsonPath, ?ConfigServiceInterface $configService = null)
    {
        $this->jsonPath = $jsonPath;
        $this->configService = $configService ?? new \Services\ConfigService();
    }

    private function load(): void
    {
        if ($this->terms !== null) {
            return;
        }

        $decoded = $this->configService->getJsonConfig($this->jsonPath);
        $sortedTerms = $decoded;
        usort($sortedTerms, function (array $a, array $b) {
            return strcasecmp($a['q'] ?? '', $b['q'] ?? '');
        });

        $this->terms = $sortedTerms;
    }

    /**
     * Get all sorted glossary terms.
     *
     * @return array
     */
    public function getAll(): array
    {
        $this->load();

        return $this->terms;
    }

    /**
     * Get distinct first letters of all glossary terms.
     *
     * @return array<string>
     */
    public function getAlphabeticalLetters(): array
    {
        $terms = $this->getAll();
        $letters = [];
        foreach ($terms as $term) {
            if (isset($term['q']) && $term['q'] !== '') {
                $firstChar = strtoupper(substr($term['q'], 0, 1));
                if (!in_array($firstChar, $letters, true)) {
                    $letters[] = $firstChar;
                }
            }
        }
        sort($letters);
        return $letters;
    }

    /**
     * Convert top glossary terms to key-value pairs for FAQ schema generation.
     *
     * @param int $limit Maximum terms to include in JSON-LD schema (prevents schema bloat)
     * @return array<string, string>
     */
    public function toFaqSchemaData(int $limit = 15): array
    {
        $terms = $this->getAll();
        $faqData = [];
        $count = 0;
        foreach ($terms as $term) {
            if (isset($term['q'], $term['a'])) {
                $faqData[$term['q']] = $term['a'];
                $count++;
                if ($count >= $limit) {
                    break;
                }
            }
        }
        return $faqData;
    }
}
