<?php

declare(strict_types=1);

namespace Core;

use Services\ConfigServiceInterface;

class FaqRepository
{
    private ?array $faqs = null;
    private string $jsonPath;
    private array $defaultCategoryLabels;
    private ConfigServiceInterface $configService;

    public function __construct(
        string $jsonPath = 'content/faqs.json',
        array $defaultCategoryLabels = [],
        ?ConfigServiceInterface $configService = null
    ) {
        $this->jsonPath = $jsonPath;
        $this->configService = $configService ?? new \Services\ConfigService();
        $this->defaultCategoryLabels = array_merge([
            'basics' => 'Basics',
            'strategies' => 'Strategies',
            'tax' => 'Tax & Risk',
            'selection' => 'Selection',
            'retirement' => 'Retirement Planning',
        ], $defaultCategoryLabels);
    }

    private function load(): void
    {
        if ($this->faqs !== null) {
            return;
        }

        $this->faqs = $this->configService->getJsonConfig($this->jsonPath);
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
                $label = $labels[$catId] ?? ucfirst(str_replace(['-', '_'], ' ', $catId));
                $seen[$catId] = true;
                $categories[] = [
                    'id' => $catId,
                    'label' => $label,
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
