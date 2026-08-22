<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Validates that CommandPaletteController enforces ModalScrollLockHelper and container-isolated keyboard handling.
 */
final class CommandPaletteScrollLockTest extends TestCase
{
    private string $controllerCode;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controllerCode = (string) file_get_contents(__DIR__ . '/../../assets/js/calculators/controllers/CommandPaletteController.ts');
    }

    public function testCommandPaletteIntegratesScrollLockHelper(): void
    {
        $this->assertStringContainsString('ModalScrollLockHelper.lock', $this->controllerCode);
        $this->assertStringContainsString('ModalScrollLockHelper.unlock', $this->controllerCode);
    }

    public function testCommandPaletteStopsKeyPropagation(): void
    {
        $this->assertStringContainsString('e.stopPropagation()', $this->controllerCode);
        $this->assertStringContainsString('e.preventDefault()', $this->controllerCode);
    }

    public function testCommandPaletteUsesLocalScrollTopMath(): void
    {
        $this->assertStringContainsString('this.resultsContainer.scrollTop', $this->controllerCode);
        $this->assertStringNotContainsString('scrollIntoView', $this->controllerCode);
    }
}
