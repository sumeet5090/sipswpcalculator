<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Core\CurrencyHelper;

class CurrencyHelperTest extends TestCase
{
    private CurrencyHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new CurrencyHelper();
    }

    /**
     * Test formatting of low integer amounts (under 1000)
     */
    public function testLowValues(): void
    {
        $this->assertEquals('₹0', $this->helper->format(0));
        $this->assertEquals('₹5', $this->helper->format(5));
        $this->assertEquals('₹99', $this->helper->format(99));
        $this->assertEquals('₹999', $this->helper->format(999));
    }

    /**
     * Test formatting of thousands (1,000 to 99,999)
     */
    public function testThousands(): void
    {
        $this->assertEquals('₹1,000', $this->helper->format(1000));
        $this->assertEquals('₹10,000', $this->helper->format(10000));
        $this->assertEquals('₹99,999', $this->helper->format(99999));
    }

    /**
     * Test formatting of Lakhs (1,00,000 to 99,99,999)
     */
    public function testLakhs(): void
    {
        $this->assertEquals('₹1,00,000', $this->helper->format(100000));
        $this->assertEquals('₹10,00,000', $this->helper->format(1000000));
        $this->assertEquals('₹99,50,000', $this->helper->format(9950000));
    }

    /**
     * Test formatting of Crores (1,00,00,000+)
     */
    public function testCrores(): void
    {
        $this->assertEquals('₹1,00,00,000', $this->helper->format(10000000));
        $this->assertEquals('₹12,34,56,789', $this->helper->format(123456789));
    }

    /**
     * Test float rounding behavior
     */
    public function testFloatRounding(): void
    {
        $this->assertEquals('₹1,001', $this->helper->format(1000.7));
        $this->assertEquals('₹1,000', $this->helper->format(1000.4));
        $this->assertEquals('₹1,00,000', $this->helper->format(99999.9));
    }
}
