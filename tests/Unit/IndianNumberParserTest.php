<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Validates IndianNumberParser client-side logic via Node.js runner across Lakhs, Crores, k, commas, and edge cases.
 */
final class IndianNumberParserTest extends TestCase
{
    /**
     * @param array<string, mixed> $inputs
     * @return array<string, mixed>
     */
    private function executeNodeParser(array $inputs): array
    {
        $nodeScript = __DIR__ . '/../run_js_calc.js';
        $payload = json_encode([
            'action' => 'parse_indian_number',
            'inputs' => $inputs
        ], JSON_THROW_ON_ERROR);

        $cmd = 'node ' . escapeshellarg($nodeScript) . ' ' . escapeshellarg($payload);
        $output = shell_exec($cmd);
        $result = json_decode((string) $output, true, 512, JSON_THROW_ON_ERROR);

        $this->assertNotNull($result, 'Node execution result must be valid JSON');
        $this->assertTrue($result['success'], 'Node execution must report success');

        return $result['results'];
    }

    public function testLakhsParsing(): void
    {
        $inputs = [
            'p1' => '1.5L',
            'p2' => '1.5 Lakh',
            'p3' => '25L',
            'p4' => '50 lac',
            'p5' => '10 lacs',
            'p6' => '₹2.5 Lakhs'
        ];

        $results = $this->executeNodeParser($inputs);

        $this->assertSame(150000, $results['p1']);
        $this->assertSame(150000, $results['p2']);
        $this->assertSame(2500000, $results['p3']);
        $this->assertSame(5000000, $results['p4']);
        $this->assertSame(1000000, $results['p5']);
        $this->assertSame(250000, $results['p6']);
    }

    public function testCroresParsing(): void
    {
        $inputs = [
            'c1' => '1 Cr',
            'c2' => '2.5 Crore',
            'c3' => '5 crs',
            'c4' => '10 crores',
            'c5' => '₹1.25 Cr'
        ];

        $results = $this->executeNodeParser($inputs);

        $this->assertSame(10000000, $results['c1']);
        $this->assertSame(25000000, $results['c2']);
        $this->assertSame(50000000, $results['c3']);
        $this->assertSame(100000000, $results['c4']);
        $this->assertSame(12500000, $results['c5']);
    }

    public function testThousandsAndCommasParsing(): void
    {
        $inputs = [
            't1' => '25k',
            't2' => '50k',
            't3' => '100 thousand',
            'c1' => '1,00,000',
            'c2' => '₹25,000',
            'c3' => '1,50,00,000'
        ];

        $results = $this->executeNodeParser($inputs);

        $this->assertSame(25000, $results['t1']);
        $this->assertSame(50000, $results['t2']);
        $this->assertSame(100000, $results['t3']);
        $this->assertSame(100000, $results['c1']);
        $this->assertSame(25000, $results['c2']);
        $this->assertSame(15000000, $results['c3']);
    }

    public function testUnitsAndPercentagesParsing(): void
    {
        $inputs = [
            'r1' => '12%',
            'r2' => '14.5 %',
            'y1' => '15 Yrs',
            'y2' => '20 years',
            'y3' => '5Y'
        ];

        $results = $this->executeNodeParser($inputs);

        $this->assertEquals(12.0, $results['r1']);
        $this->assertEquals(14.5, $results['r2']);
        $this->assertEquals(15.0, $results['y1']);
        $this->assertEquals(20.0, $results['y2']);
        $this->assertEquals(5.0, $results['y3']);
    }

    public function testEdgeCases(): void
    {
        $inputs = [
            'zero' => 0,
            'zero_str' => '0',
            'empty' => '',
            'invalid' => 'invalid text'
        ];

        $results = $this->executeNodeParser($inputs);

        $this->assertSame(0, $results['zero']);
        $this->assertSame(0, $results['zero_str']);
        $this->assertNull($results['empty']); // JSON null from NaN
        $this->assertNull($results['invalid']); // JSON null from NaN
    }
}
