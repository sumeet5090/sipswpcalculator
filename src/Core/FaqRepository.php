<?php

declare(strict_types=1);

namespace Core;

class FaqRepository
{
    private ?array $faqs = null;
    private string $jsonPath;
    private array $defaultCategoryLabels;

    public function __construct(string $jsonPath, array $defaultCategoryLabels = [])
    {
        $this->jsonPath = $jsonPath;
        $this->defaultCategoryLabels = array_merge([
            'basics'     => 'Basics',
            'strategies' => 'Strategies',
            'tax'        => 'Tax & Risk',
            'selection'  => 'Selection',
            'retirement' => 'Retirement Planning',
        ], $defaultCategoryLabels);
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
     * Get FAQ categories metadata derived dynamically from FAQ data.
     */
    public function getFaqCategories(?array $customLabels = null): array
    {
        $this->load();
        $labels = array_merge($this->defaultCategoryLabels, $customLabels ?? []);

        $categories = [];
        $seen = [];

        foreach ($this->faqs as $faq) {
            $catId = $faq['category'] ?? '';
            if ($catId !== '' && !isset($seen[$catId])) {
                $seen[$catId] = true;
                $categories[] = [
                    'id'    => $catId,
                    'label' => $labels[$catId] ?? ucfirst(str_replace('_', ' ', $catId)),
                ];
            }
        }

        return $categories;
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
