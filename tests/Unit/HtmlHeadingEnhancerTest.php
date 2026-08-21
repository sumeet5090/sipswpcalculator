<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Services\HtmlHeadingEnhancer;

class HtmlHeadingEnhancerTest extends TestCase
{
    private HtmlHeadingEnhancer $enhancer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->enhancer = new HtmlHeadingEnhancer();
    }

    public function testEnhanceHeadingsInjectsSlugIdAndScrollClass(): void
    {
        $rawHtml = '<h2>Understanding SIP Compounding</h2><h3 class="custom-class">Step Up Benefits</h3>';
        $enhanced = $this->enhancer->enhanceHeadings($rawHtml);

        $this->assertStringContainsString('<h2 id="understanding-sip-compounding" class="scroll-mt-28">Understanding SIP Compounding</h2>', $enhanced);
        $this->assertStringContainsString('<h3 id="step-up-benefits" class="custom-class scroll-mt-28">Step Up Benefits</h3>', $enhanced);
    }
}
