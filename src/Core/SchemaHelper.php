<?php

declare(strict_types=1);

namespace Core;

class SchemaHelper
{
    /**
     * Generates BreadcrumbList Schema.org JSON-LD.
     */
    public function getBreadcrumbs(array $items): string
    {
        $itemListElement = [];
        $position = 1;
        foreach ($items as $name => $item) {
            $itemListElement[] = [
                "@type" => "ListItem",
                "position" => $position++,
                "name" => $name,
                "item" => "https://sipswpcalculator.com" . $item,
            ];
        }

        return json_encode([
            "@context" => "https://schema.org",
            "@type" => "BreadcrumbList",
            "itemListElement" => $itemListElement,
        ], JSON_UNESCAPED_SLASHES);
    }

    /**
     * Generates FAQPage Schema.org JSON-LD.
     */
    public function getFAQ(array $faqs): string
    {
        $mainEntity = [];
        foreach ($faqs as $question => $answer) {
            $mainEntity[] = [
                "@type" => "Question",
                "name" => $question,
                "acceptedAnswer" => [
                    "@type" => "Answer",
                    "text" => $answer,
                ],
            ];
        }

        return json_encode([
            "@context" => "https://schema.org",
            "@type" => "FAQPage",
            "mainEntity" => $mainEntity,
        ], JSON_UNESCAPED_SLASHES);
    }

    /**
     * Generates Article Schema.org JSON-LD.
     */
    public function getArticle(string $headline, string $datePublished, string $dateModified): string
    {
        return json_encode([
            "@context" => "https://schema.org",
            "@type" => "Article",
            "headline" => $headline,
            "author" => [
                "@type" => "Person",
                "name" => "Sumeet Boga",
                "url" => "https://sipswpcalculator.com/about",
            ],
            "datePublished" => $datePublished,
            "dateModified" => $dateModified,
        ], JSON_UNESCAPED_SLASHES);
    }
}
