<?php

declare(strict_types=1);

namespace Core;

class FaqRepository
{
    private array $faqs;

    public function __construct()
    {
        $this->faqs = require __DIR__ . '/Config/faqs.php';
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
