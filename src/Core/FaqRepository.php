<?php

declare(strict_types=1);

namespace Core;

class FaqRepository
{
    private ?array $faqs = null;
    private string $jsonPath;

    public function __construct(string $jsonPath)
    {
        $this->jsonPath = $jsonPath;
    }

    private function load(): void
    {
        if ($this->faqs !== null) {
            return;
        }

        if (!file_exists($this->jsonPath)) {
            $this->faqs = [];
            return;
        }

        $jsonContent = file_get_contents($this->jsonPath);
        $decoded = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Failed to parse FAQs JSON: " . json_last_error_msg());
            $this->faqs = [];
        } else {
            $this->faqs = is_array($decoded) ? $decoded : [];
        }
    }

    /**
     * Get default FAQ categories metadata.
     */
    public function getFaqCategories(): array
    {
        return [
            ['id' => 'basics', 'label' => 'Basics'],
            ['id' => 'strategies', 'label' => 'Strategies'],
            ['id' => 'tax', 'label' => 'Tax & Risk'],
            ['id' => 'selection', 'label' => 'Selection'],
            ['id' => 'retirement', 'label' => 'Retirement Planning'],
        ];
    }

    /**
     * Get all FAQs.
     */
    public function getAll(): array
    {
        $this->load();
        return $this->faqs;
    }

    /**
     * Get FAQs by specific tag.
     */
    public function getByTag(string $tag): array
    {
        $this->load();
        return array_values(array_filter($this->faqs, function (array $faq) use ($tag) {
            return in_array($tag, $faq['tags'] ?? [], true);
        }));
    }

    /**
     * Get FAQs by specific category.
     */
    public function getByCategory(string $category): array
    {
        $this->load();
        return array_values(array_filter($this->faqs, function (array $faq) use ($category) {
            return ($faq['category'] ?? '') === $category;
        }));
    }
}
