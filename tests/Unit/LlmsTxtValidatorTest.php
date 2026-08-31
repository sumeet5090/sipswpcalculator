<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class LlmsTxtValidatorTest extends TestCase
{
    public function testLlmsTxtExistsAndContainsAllCalculatorRoutes(): void
    {
        $llmsPath = __DIR__ . '/../../llms.txt';
        $this->assertFileExists($llmsPath);

        $content = (string) file_get_contents($llmsPath);
        $this->assertNotEmpty($content);
        $this->assertStringContainsString('sipswpcalculator.com', $content);

        $routesConfig = require __DIR__ . '/../../src/Core/Config/routes.php';
        foreach (array_keys($routesConfig['calculators']) as $path) {
            $this->assertStringContainsString(
                $path,
                $content,
                "llms.txt is missing calculator path: {$path}"
            );
        }
    }
}
