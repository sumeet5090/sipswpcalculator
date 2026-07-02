<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Core\CurrencyHelper;

class CurrencyHelperTest extends TestCase
{
    /**
     * Test formatting of low integer amounts (under 1000)
     */
    public function testLowValues(): void
    {
        $this->assertEquals('₹ 0', CurrencyHelper::formatInr(0));
        $this->assertEquals('₹ 5', CurrencyHelper::formatInr(5));
        $this->assertEquals('₹ 99', CurrencyHelper::formatInr(99));
        $this->assertEquals('₹ 999', CurrencyHelper::formatInr(999));
    }

    /**
     * Test formatting of thousands (1,000 to 99,999)
     */
    public function testThousands(): void
    {
        $this->assertEquals('₹ 1,000', CurrencyHelper::formatInr(1000));
        $this->assertEquals('₹ 10,000', CurrencyHelper::formatInr(10000));
        $this->assertEquals('₹ 99,999', CurrencyHelper::formatInr(99999));
    }

    /**
     * Test formatting of Lakhs (1,00,000 to 99,99,999)
     */
    public function testLakhs(): void
    {
        $this->assertEquals('₹ 1,00,000', CurrencyHelper::formatInr(100000));
        $this->assertEquals('₹ 10,00,000', CurrencyHelper::formatInr(1000000));
        $this->assertEquals('₹ 99,50,000', CurrencyHelper::formatInr(9950000));
    }

    /**
     * Test formatting of Crores (1,00,00,000+)
     */
    public function testCrores(): void
    {
        $this->assertEquals('₹ 1,00,00,000', CurrencyHelper::formatInr(10000000));
        $this->assertEquals('₹ 12,34,56,789', CurrencyHelper::formatInr(123456789));
    }

    /**
     * Test float rounding behavior
     */
    public function testFloatRounding(): void
    {
        $this->assertEquals('₹ 1,001', CurrencyHelper::formatInr(1000.7));
        $this->assertEquals('₹ 1,000', CurrencyHelper::formatInr(1000.4));
        $this->assertEquals('₹ 1,00,000', CurrencyHelper::formatInr(99999.9));
    }
}
