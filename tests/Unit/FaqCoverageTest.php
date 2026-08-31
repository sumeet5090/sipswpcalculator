<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class FaqCoverageTest extends TestCase
{
    public function testAllCalculatorSlugsHaveMinimumFaqs(): void
    {
        $routesConfig = require __DIR__ . '/../../src/Core/Config/routes.php';
        $faqsPath = __DIR__ . '/../../content/faqs.json';

        $this->assertFileExists($faqsPath);
        $faqs = json_decode((string) file_get_contents($faqsPath), true);
        $this->assertIsArray($faqs);

        foreach (array_keys($routesConfig['calculators']) as $path) {
            $slug = ltrim($path, '/');
            $tagged = array_filter($faqs, static function (array $faq) use ($slug): bool {
                return in_array($slug, $faq['tags'] ?? [], true);
            });

            $this->assertGreaterThanOrEqual(
                3,
                count($tagged),
                "Calculator '{$slug}' has fewer than 3 FAQs tagged in faqs.json (found " . count($tagged) . ")"
            );
        }
    }
}
