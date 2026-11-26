<?php

declare(strict_types=1);

namespace Core;

use Services\ConfigServiceInterface;

class FaqRepository
{
    /** @var array<int, array<string, mixed>>|null */
    private ?array $faqs = null;
    private string $jsonPath;
    /** @var array<string, string> */
    private array $defaultCategoryLabels;
    private ConfigServiceInterface $configService;

    /**
     * @param array<string, string> $defaultCategoryLabels
     */
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

    /**
     * @return array<int, array<string, mixed>>
     */
    private function load(): array
    {
        if ($this->faqs === null) {
            /** @var array<int, array<string, mixed>> $loaded */
            $loaded = $this->configService->getJsonConfig($this->jsonPath);
            $this->faqs = $loaded;
        }
        return $this->faqs;
    }

    /**
     * Get FAQ categories metadata derived dynamically from FAQ data.
     *
     * @param array<string, string>|null $customLabels
     * @return list<array{id: string, label: string}>
     */
    public function getFaqCategories(?array $customLabels = null): array
    {
        $faqs = $this->load();

        $labels = array_merge($this->defaultCategoryLabels, $customLabels ?? []);

        $categories = [];
        $seen = [];

        foreach ($faqs as $faq) {
            $catId = (string) ($faq['category'] ?? '');
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
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAll(): array
    {
        return $this->load();
    }

    /**
     * Get FAQs by specific tag.
     *
     * @return list<array<string, mixed>>
     */
    public function getByTag(string $tag): array
    {
        return array_values(array_filter($this->load(), function (array $faq) use ($tag) {
            return in_array($tag, is_array($faq['tags'] ?? null) ? $faq['tags'] : [], true);
        }));
    }

    /**
     * Get FAQs by specific category.
     *
     * @return list<array<string, mixed>>
     */
    public function getByCategory(string $category): array
    {
        return array_values(array_filter($this->load(), function (array $faq) use ($category) {
            return ($faq['category'] ?? '') === $category;
        }));
    }
}
