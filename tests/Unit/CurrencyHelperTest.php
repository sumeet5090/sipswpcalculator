<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\CurrencyHelper;
use PHPUnit\Framework\TestCase;

class CurrencyHelperTest extends TestCase
{
    private CurrencyHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new CurrencyHelper();
    }

    public function testFormatsSmallNumbers(): void
    {
        $this->assertSame('₹0', $this->helper->format(0));
        $this->assertSame('₹50', $this->helper->format(50));
        $this->assertSame('₹500', $this->helper->format(500));
    }

    public function testFormatsThousands(): void
    {
        $this->assertSame('₹1,000', $this->helper->format(1000));
        $this->assertSame('₹25,000', $this->helper->format(25000));
        $this->assertSame('₹99,999', $this->helper->format(99999));
    }

    public function testFormatsLakhs(): void
    {
        $this->assertSame('₹1,00,000', $this->helper->format(100000));
        $this->assertSame('₹15,50,000', $this->helper->format(1550000));
        $this->assertSame('₹99,99,999', $this->helper->format(9999999));
    }

    public function testFormatsCrores(): void
    {
        $this->assertSame('₹1,00,00,000', $this->helper->format(10000000));
        $this->assertSame('₹12,34,56,789', $this->helper->format(123456789));
    }

    public function testFormatsNegativeNumbers(): void
    {
        $this->assertSame('-₹500', $this->helper->format(-500));
        $this->assertSame('-₹1,00,000', $this->helper->format(-100000));
    }

    public function testRoundsDecimalInputs(): void
    {
        $this->assertSame('₹1,500', $this->helper->format(1499.8));
        $this->assertSame('₹1,499', $this->helper->format(1499.2));
    }

    public function testFormatDynamicInr(): void
    {
        $this->assertSame('₹1 Crore', $this->helper->formatDynamic(10000000));
        $this->assertSame('₹2.50 Crore', $this->helper->formatDynamic(25000000));
        $this->assertSame('₹15 Lakh', $this->helper->formatDynamic(1500000));
        $this->assertSame('₹25k', $this->helper->formatDynamic(25000));
        $this->assertSame('-₹50 Lakh', $this->helper->formatDynamic(-5000000));
        $this->assertSame('₹0', $this->helper->formatDynamic(0));
        $this->assertSame('₹0', $this->helper->formatDynamic(-0.4));
    }

    public function testFormatDynamicInternationalCurrencies(): void
    {
        $this->assertSame('$2.50B', $this->helper->formatDynamic(2500000000, 'USD'));
        $this->assertSame('€15M', $this->helper->formatDynamic(15000000, 'EUR'));
        $this->assertSame('£250k', $this->helper->formatDynamic(250000, 'GBP'));
        $this->assertSame('AED1.50M', $this->helper->formatDynamic(1500000, 'AED'));
    }

    public function testNegativeZeroFormatting(): void
    {
        $this->assertSame('₹0', $this->helper->format(-0.0));
        $this->assertSame('₹0', $this->helper->format(-0.4));
        $this->assertSame('-₹1', $this->helper->format(-0.6));
    }
}
