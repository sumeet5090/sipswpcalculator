<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class FormStructureParityTest extends TestCase
{
    /**
     * Test that sip-fields.twig exposes all inputs directly without collapsed details accordions.
     */
    public function testSipFieldsStructureNoDetailsAccordion(): void
    {
        $path = __DIR__ . '/../../src/Views/components/forms/sip-fields.twig';
        $this->assertFileExists($path);
        $content = (string) file_get_contents($path);

        // Ensure <details> and <summary> are removed
        $this->assertStringNotContainsString('<details', $content);
        $this->assertStringNotContainsString('<summary', $content);
        $this->assertStringNotContainsString('id="sip-advanced-details"', $content);

        // Ensure all primary & secondary inputs are present
        $this->assertStringContainsString("'id': 'sip'", $content);
        $this->assertStringContainsString("'id': 'years'", $content);
        $this->assertStringContainsString("'id': 'rate'", $content);
        $this->assertStringContainsString("'id': 'stepup'", $content);
        $this->assertStringContainsString("'id': 'inflation'", $content);

        // Ensure Smart Nudge popover trigger is intact
        $this->assertStringContainsString('id="rate-nudge-btn"', $content);
        $this->assertStringContainsString('Not sure?', $content);

        // Ensure semantic section header exists
        $this->assertStringContainsString('Adjustments & Inflation', $content);
    }

    /**
     * Test that swp-fields.twig exposes all inputs directly without collapsed details accordions.
     */
    public function testSwpFieldsStructureNoDetailsAccordion(): void
    {
        $path = __DIR__ . '/../../src/Views/components/forms/swp-fields.twig';
        $this->assertFileExists($path);
        $content = (string) file_get_contents($path);

        // Ensure <details> and <summary> are removed
        $this->assertStringNotContainsString('<details', $content);
        $this->assertStringNotContainsString('<summary', $content);
        $this->assertStringNotContainsString('id="swp-advanced-details"', $content);

        // Ensure all primary & secondary inputs are present
        $this->assertStringContainsString("'id': 'swp_withdrawal'", $content);
        $this->assertStringContainsString("'id': 'swp_years'", $content);
        $this->assertStringContainsString("'id': 'swp_rate'", $content);
        $this->assertStringContainsString("'id': 'swp_stepup'", $content);

        // Ensure semantic section header exists
        $this->assertStringContainsString('Inflation Escalation', $content);
    }

    /**
     * Test that lumpsum-only-fields.twig exposes all inputs directly without collapsed details accordions.
     */
    public function testLumpsumOnlyFieldsStructureNoDetailsAccordion(): void
    {
        $path = __DIR__ . '/../../src/Views/components/forms/lumpsum-only-fields.twig';
        $this->assertFileExists($path);
        $content = (string) file_get_contents($path);

        // Ensure <details> and <summary> are removed
        $this->assertStringNotContainsString('<details', $content);
        $this->assertStringNotContainsString('<summary', $content);
        $this->assertStringNotContainsString('id="lumpsum-advanced-details"', $content);

        // Ensure all primary & secondary inputs are present
        $this->assertStringContainsString("'id': 'lumpsum'", $content);
        $this->assertStringContainsString("'id': 'years'", $content);
        $this->assertStringContainsString("'id': 'rate'", $content);
        $this->assertStringContainsString("'id': 'inflation'", $content);

        // Ensure semantic section header exists
        $this->assertStringContainsString('Adjustments & Inflation', $content);
    }
}
