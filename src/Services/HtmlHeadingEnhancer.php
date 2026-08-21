<?php

declare(strict_types=1);

namespace Services;

/**
 * HtmlHeadingEnhancer
 * Dedicated domain service to parse rendered HTML content and inject slug IDs and scroll margin CSS classes
 * into h2/h3 heading tags for SSR deep linking and Table of Contents (TOC) parity.
 */
class HtmlHeadingEnhancer
{
    /**
     * Inject slug IDs and scroll-margin-top into h2 and h3 headings.
     *
     * @param string $html Raw HTML content
     * @return string Enhanced HTML content
     */
    public function enhanceHeadings(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        return (string) preg_replace_callback('/<h([23])(?:\s+class="([^"]*)")?>(.*?)<\/h\1>/i', function (array $matches) {
            $level = $matches[1];
            $existingClass = $matches[2];
            $text = $matches[3];
            $plainText = strip_tags($text);
            $slug = strtolower(trim((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $plainText), '-'));
            $classes = trim($existingClass . ' scroll-mt-28');
            return "<h{$level} id=\"{$slug}\" class=\"{$classes}\">{$text}</h{$level}>";
        }, $html);
    }
}
