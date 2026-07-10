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
    public function getArticle(string $headline, string $description, string $url, string $imageUrl, string $datePublished, string $dateModified): string
    {
        return json_encode([
            "@context" => "https://schema.org",
            "@type" => "Article",
            "mainEntityOfPage" => [
                "@type" => "WebPage",
                "@id" => $url,
            ],
            "headline" => $headline,
            "description" => $description,
            "image" => $imageUrl,
            "author" => [
                "@type" => "Person",
                "name" => "Sumeet Boga",
                "url" => "https://sipswpcalculator.com/about",
            ],
            "publisher" => [
                "@type" => "Organization",
                "name" => "SIP SWP Calculator",
                "logo" => [
                    "@type" => "ImageObject",
                    "url" => "https://sipswpcalculator.com/assets/favicon.png"
                ]
            ],
            "datePublished" => $datePublished,
            "dateModified" => $dateModified,
        ], JSON_UNESCAPED_SLASHES);
    }

    /**
     * Generates WebPage Schema.org JSON-LD.
     */
    public function getWebPage(string $title, string $description, string $url): string
    {
        return json_encode([
            "@context" => "https://schema.org",
            "@type" => "WebPage",
            "name" => $title,
            "description" => $description,
            "url" => $url,
            "speakable" => [
                "@type" => "SpeakableSpecification",
                "cssSelector" => ["h1", ".markdown-content p:first-of-type"]
            ]
        ], JSON_UNESCAPED_SLASHES);
    }

    /**
     * Generates SoftwareApplication Schema.org JSON-LD for calculators.
     */
    public function getSoftwareApplication(string $name, string $description, string $url, string $category = "FinanceApplication", array $rating = []): string
    {
        $schema = [
            "@context" => "https://schema.org",
            "@type" => "SoftwareApplication",
            "name" => $name,
            "description" => $description,
            "url" => $url,
            "applicationCategory" => $category,
            "operatingSystem" => "Web",
            "offers" => [
                "@type" => "Offer",
                "price" => "0",
                "priceCurrency" => "INR"
            ]
        ];

        if (!empty($rating) && isset($rating['ratingValue'], $rating['ratingCount'])) {
            $schema['aggregateRating'] = [
                "@type" => "AggregateRating",
                "ratingValue" => $rating['ratingValue'],
                "ratingCount" => $rating['ratingCount'],
                "bestRating" => "5",
                "worstRating" => "1"
            ];
        }

        return json_encode($schema, JSON_UNESCAPED_SLASHES);
    }
}
