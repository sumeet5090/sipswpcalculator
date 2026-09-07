<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * EditorialReadingErgonomicsTest
 *
 * Validates the editorial reading experience, touch ergonomics,
 * responsive mobile navigation, dynamic INR amount hydration,
 * and Table of Contents bottom sheet infrastructure.
 */
final class EditorialReadingErgonomicsTest extends TestCase
{
    private string $genericPostTemplate;
    private string $markdownGuide;
    private string $scriptCode;
    private string $tocCode;

    protected function setUp(): void
    {
        parent::setUp();
        $this->genericPostTemplate = (string) file_get_contents(
            __DIR__ . '/../../src/Views/layouts/generic-post.twig'
        );
        $this->markdownGuide = (string) file_get_contents(
            __DIR__ . '/../../content/blog/growth/sip-for-beginners.md'
        );
        $this->scriptCode = (string) file_get_contents(
            __DIR__ . '/../../resources/js/script.ts'
        );
        $this->tocCode = (string) file_get_contents(
            __DIR__ . '/../../assets/js/toc.ts'
        );
    }

    public function testGenericPostTemplateIncludesReadingDockAndToc(): void
    {
        $this->assertStringContainsString('id="reading-progress-bar"', $this->genericPostTemplate);
        $this->assertStringContainsString('id="reading-progress-container"', $this->genericPostTemplate);
        $this->assertStringContainsString('id="toc-list"', $this->genericPostTemplate);
        $this->assertStringContainsString('id="mobile-reading-dock"', $this->genericPostTemplate);
        $this->assertStringContainsString('id="open-mobile-toc-btn"', $this->genericPostTemplate);
        $this->assertStringContainsString('id="mobile-toc-sheet"', $this->genericPostTemplate);
        $this->assertStringContainsString('id="mobile-toc-list"', $this->genericPostTemplate);
        $this->assertStringContainsString('id="mobile-reading-share-btn"', $this->genericPostTemplate);
        $this->assertStringContainsString('modal-drag-handle', $this->genericPostTemplate);
        $this->assertStringContainsString('Back to {{ cat_name }}', $this->genericPostTemplate);
    }

    public function testMarkdownTableErgonomicsAndStickyColumns(): void
    {
        $this->assertStringContainsString('table-sticky-col-0', $this->markdownGuide);
        $this->assertStringContainsString('table-scroll-mask-end', $this->markdownGuide);
        $this->assertGreaterThanOrEqual(3, substr_count($this->markdownGuide, 'table-scroll-mask-end'));
    }

    public function testDynamicAmountsHaveDefaultSSRFormattedValues(): void
    {
        // Ensure no empty dynamic-amount spans exist
        $this->assertDoesNotMatchString('/<span class="dynamic-amount"[^>]*><\/span>/', $this->markdownGuide);

        // Verify key milestones are present with Indian Rupee symbol
        $this->assertStringContainsString('data-amount-inr="500">₹500</span>', $this->markdownGuide);
        $this->assertStringContainsString('data-amount-inr="1765000">₹17,65,000</span>', $this->markdownGuide);
        $this->assertStringContainsString('data-amount-inr="500000">₹5,00,000</span>', $this->markdownGuide);
        $this->assertStringContainsString('data-amount-inr="1000000">₹10,00,000</span>', $this->markdownGuide);
        $this->assertStringContainsString('data-amount-inr="10000">₹10,000</span>', $this->markdownGuide);
    }

    public function testClientSideHydrationAndReadingProgressScripts(): void
    {
        $this->assertStringContainsString('hydrateDynamicAmounts', $this->scriptCode);
        $this->assertStringContainsString('initReadingProgress', $this->scriptCode);
        $this->assertStringContainsString('reading-progress-bar', $this->scriptCode);
        $this->assertStringContainsString('mobile-reading-share-btn', $this->scriptCode);
        $this->assertStringContainsString('WebHapticEngine.triggerTick', $this->scriptCode);
    }

    public function testTocClientModuleSupportsMobileAndDesktop(): void
    {
        $this->assertStringContainsString('toc-list', $this->tocCode);
        $this->assertStringContainsString('mobile-toc-list', $this->tocCode);
        $this->assertStringContainsString('mobile-toc-sheet', $this->tocCode);
        $this->assertStringContainsString('open-mobile-toc-btn', $this->tocCode);
        $this->assertStringContainsString('close-mobile-toc-btn', $this->tocCode);
        $this->assertStringContainsString('WebHapticEngine.triggerTick', $this->tocCode);
        $this->assertStringContainsString('IntersectionObserver', $this->tocCode);
    }

    public function testInteractiveWealthSandboxCtaStructure(): void
    {
        $relatedResources = (string) file_get_contents(
            __DIR__ . '/../../src/Views/components/related-resources.twig'
        );

        $this->assertStringContainsString('aria-label="Simulate Your Wealth Journey"', $relatedResources);
        $this->assertStringContainsString('SEBI / AMFI Formula Certified', $relatedResources);
        $this->assertStringContainsString('100% Client-Side Privacy', $relatedResources);
        $this->assertStringContainsString('Simulate My Compounding Goals', $relatedResources);
        $this->assertStringContainsString('Simulate My Retirement Drawdown', $relatedResources);
        $this->assertStringContainsString('Launch Head-to-Head Comparison', $relatedResources);
        $this->assertStringContainsString('shadow-card hover:shadow-card-hover', $relatedResources);
    }

    private function assertDoesNotMatchString(string $pattern, string $string): void
    {
        $this->assertSame(0, preg_match($pattern, $string), "Failed asserting that string does not match regex {$pattern}");
    }
}
