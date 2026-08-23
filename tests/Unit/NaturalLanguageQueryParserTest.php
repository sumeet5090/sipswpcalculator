<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Validates NaturalLanguageQueryParser for intelligent command palette parameter extraction.
 */
final class NaturalLanguageQueryParserTest extends TestCase
{
    private function parseQuery(string $query): array
    {
        $nodeScript = __DIR__ . '/../run_js_calc.js';
        $payload = json_encode([
            'action' => 'parse_nlp_query',
            'query' => $query
        ], JSON_THROW_ON_ERROR);

        $cmd = 'node ' . escapeshellarg($nodeScript) . ' ' . escapeshellarg($payload);
        $output = shell_exec($cmd);
        $result = json_decode((string) $output, true, 512, JSON_THROW_ON_ERROR);

        $this->assertNotNull($result, 'Node execution result must be valid JSON');
        $this->assertTrue($result['success'], 'Node execution must report success');

        return $result['result'];
    }

    public function testParseSipWithStandardShorthands(): void
    {
        $res = $this->parseQuery('sip 25k 15y 12%');
        $this->assertTrue($res['isValid']);
        $this->assertEquals('sip', $res['type']);
        $this->assertEquals(25000, $res['sip']);
        $this->assertEquals(15, $res['years']);
        $this->assertEquals(12, $res['rate']);

        $res2 = $this->parseQuery('₹10,000 monthly sip for 20 years');
        $this->assertTrue($res2['isValid']);
        $this->assertEquals('sip', $res2['type']);
        $this->assertEquals(10000, $res2['sip']);
        $this->assertEquals(20, $res2['years']);
    }

    public function testParseSwpIntent(): void
    {
        $res = $this->parseQuery('swp 50000 20 yrs 8%');
        $this->assertTrue($res['isValid']);
        $this->assertEquals('swp', $res['type']);
        $this->assertEquals(50000, $res['swp_withdrawal']);
        $this->assertEquals(20, $res['swp_years']);
        $this->assertEquals(8, $res['swp_rate']);
    }

    public function testParseTargetGoalIntent(): void
    {
        $res = $this->parseQuery('target 1cr in 10 years');
        $this->assertTrue($res['isValid']);
        $this->assertEquals('target', $res['type']);
        $this->assertEquals(10000000, $res['target_corpus']);
        $this->assertEquals(10, $res['years']);
    }

    public function testParseLumpsumIntent(): void
    {
        $res = $this->parseQuery('5L lumpsum for 15 years');
        $this->assertTrue($res['isValid']);
        $this->assertEquals('lumpsum', $res['type']);
        $this->assertEquals(500000, $res['lumpsum']);
        $this->assertEquals(15, $res['years']);
    }

    public function testNonFinancialQueryReturnsInvalid(): void
    {
        $res = $this->parseQuery('how does compound interest work?');
        $this->assertFalse($res['isValid']);
    }
}
