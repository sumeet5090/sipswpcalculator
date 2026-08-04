<?php

declare(strict_types=1);

namespace Core;

class FaqRepository
{
    private array $faqs;

    public function __construct(?string $jsonPath = null)
    {
        $path = $jsonPath ?? __DIR__ . '/../../content/faqs.json';
        if (!file_exists($path)) {
            $this->faqs = [];
            return;
        }

        $jsonContent = file_get_contents($path);
        $decoded = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Failed to parse FAQs JSON: " . json_last_error_msg());
            $this->faqs = [];
        } else {
            $this->faqs = is_array($decoded) ? $decoded : [];
        }
    }

    /**
     * Get all FAQs.
     */
    public function getAll(): array
    {
        return $this->faqs;
    }

    /**
     * Get FAQs by specific tag.
     */
    public function getByTag(string $tag): array
    {
        return array_values(array_filter($this->faqs, function (array $faq) use ($tag) {
            return in_array($tag, $faq['tags'] ?? [], true);
        }));
    }

    /**
     * Get FAQs by specific category.
     */
    public function getByCategory(string $category): array
    {
        return array_values(array_filter($this->faqs, function (array $faq) use ($category) {
            return ($faq['category'] ?? '') === $category;
        }));
    }
}
