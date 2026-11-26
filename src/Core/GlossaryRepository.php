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
    /** @var array<int, array<string, mixed>>|null */
    private ?array $terms = null;
    private string $jsonPath;
    private ConfigServiceInterface $configService;

    public function __construct(string $jsonPath, ?ConfigServiceInterface $configService = null)
    {
        $this->jsonPath = $jsonPath;
        $this->configService = $configService ?? new \Services\ConfigService();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function load(): array
    {
        if ($this->terms === null) {
            /** @var array<int, array<string, mixed>> $sortedTerms */
            $sortedTerms = $this->configService->getJsonConfig($this->jsonPath);
            usort($sortedTerms, function (array $a, array $b) {
                return strcasecmp((string) ($a['q'] ?? ''), (string) ($b['q'] ?? ''));
            });
            $this->terms = $sortedTerms;
        }
        return $this->terms;
    }

    /**
     * Get all sorted glossary terms.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAll(): array
    {
        return $this->load();
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
