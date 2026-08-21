<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Factories\HomeSchemaBuilder;
use Core\SchemaHelper;
use Core\SiteConfig;
use PHPUnit\Framework\TestCase;

class HomeSchemaBuilderTest extends TestCase
{
    public function testBuildsAllHomeSchemas(): void
    {
        $siteConfig = new SiteConfig('https://sipswpcalculator.com');
        $builder = new HomeSchemaBuilder($siteConfig);

        $page_config = [
            'schema_name' => 'Advanced SIP & SWP Calculator',
            'meta_desc' => 'Calculates returns with annual top up.',
        ];

        $schemas = $builder->build($page_config, [], '2026-08-02');

        $this->assertCount(6, $schemas);

        $types = [];
        foreach ($schemas as $json) {
            $decoded = json_decode($json, true);
            $this->assertIsArray($decoded);
            $types[] = $decoded['@type'] ?? null;
        }

        $this->assertContains('SoftwareApplication', $types);
        $this->assertContains('FinancialProduct', $types);
        $this->assertContains('WebSite', $types);
        $this->assertContains('Organization', $types);
        $this->assertContains('Person', $types);
        $this->assertContains('HowTo', $types);
    }
}
