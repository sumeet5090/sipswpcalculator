<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Exceptions\ConfigurationException;
use Core\SiteConfig;
use PHPUnit\Framework\TestCase;

class SiteConfigTest extends TestCase
{
    public function testValidBaseUrlNormalization(): void
    {
        $config = new SiteConfig('https://sipswpcalculator.com/');
        $this->assertSame('https://sipswpcalculator.com', $config->getBaseUrl());
    }

    public function testGetUrlWithRootAndEmpty(): void
    {
        $config = new SiteConfig('https://sipswpcalculator.com');
        $this->assertSame('https://sipswpcalculator.com/', $config->getUrl(''));
        $this->assertSame('https://sipswpcalculator.com/', $config->getUrl('/'));
    }

    public function testGetUrlWithPathAndQuery(): void
    {
        $config = new SiteConfig('https://sipswpcalculator.com');
        $this->assertSame('https://sipswpcalculator.com/sip-calculator', $config->getUrl('/sip-calculator'));
        $this->assertSame('https://sipswpcalculator.com/sip-calculator', $config->getUrl('sip-calculator'));
        $this->assertSame('https://sipswpcalculator.com/resource/growth/sip-guide?ref=1', $config->getUrl('/resource/growth/sip-guide?ref=1'));
    }

    public function testGetUrlSanitizesConsecutiveInternalSlashes(): void
    {
        $config = new SiteConfig('https://sipswpcalculator.com');
        $this->assertSame('https://sipswpcalculator.com/resource/growth/post', $config->getUrl('///resource//growth///post'));
    }

    public function testEmptyBaseUrlThrowsConfigurationException(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("Invalid base URL");
        new SiteConfig('');
    }

    public function testSchemelessBaseUrlThrowsConfigurationException(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("Invalid base URL");
        new SiteConfig('sipswpcalculator.com');
    }
}
