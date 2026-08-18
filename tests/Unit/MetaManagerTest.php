<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Exceptions\ConfigurationException;
use Core\MetaManager;
use Core\SiteConfig;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

class MetaManagerTest extends TestCase
{
    private SiteConfig $siteConfig;

    protected function setUp(): void
    {
        $this->siteConfig = new SiteConfig('https://sipswpcalculator.com');
    }

    public function testThrowsExceptionIfUrlPathLacksLeadingSlash(): void
    {
        $metaManager = new MetaManager($this->siteConfig);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('URL path must start with a slash: invalid-slug');

        $metaManager->buildFromMetadata([], 'invalid-slug');
    }

    public function testThrowsExceptionOnMissingMetaPagesJson(): void
    {
        $metaManager = new MetaManager($this->siteConfig, '/non/existent/path/meta_pages.json');

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Metadata pages configuration missing at: /non/existent/path/meta_pages.json');

        $metaManager->getMeta('home');
    }

    public function testThrowsExceptionOnInvalidJsonFormat(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'meta_pages');
        file_put_contents($tempFile, '{ invalid json }');

        $metaManager = new MetaManager($this->siteConfig, $tempFile);

        try {
            $this->expectException(ConfigurationException::class);
            $this->expectExceptionMessage('Failed to parse metadata pages configuration: Syntax error');
            $metaManager->getMeta('home');
        } finally {
            unlink($tempFile);
        }
    }

    public function testBuildsCanonicalUrlCorrectly(): void
    {
        $metaManager = new MetaManager($this->siteConfig);

        $result = $metaManager->buildFromMetadata([], '/test-path');

        $this->assertEquals('https://sipswpcalculator.com/test-path', $result['canonical']);
    }

    public function testHomePageInjectsDefaultCanonical(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'meta_pages');
        file_put_contents($tempFile, json_encode([
            'home' => [
                'title' => 'Home Page'
            ]
        ]));

        $metaManager = new MetaManager($this->siteConfig, $tempFile);

        try {
            $result = $metaManager->getMeta('home');
            $this->assertEquals('https://sipswpcalculator.com/', $result['canonical']);
        } finally {
            unlink($tempFile);
        }
    }
}
