<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Http\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    public function testGetUriStripsQueryParametersAndNormalizesSlashes(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/sip-calculator?utm_source=google&fbclid=abc123xyz'
        ];
        $request = new Request([], [], $server);

        $this->assertSame('/sip-calculator', $request->getUri());
    }

    public function testGetUriNormalizesMultipleConsecutiveSlashes(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '///resource//growth///what-is-sip?track=1'
        ];
        $request = new Request([], [], $server);

        $this->assertSame('/resource/growth/what-is-sip', $request->getUri());
    }

    public function testGetUriReturnsSlashForEmptyUri(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => ''
        ];
        $request = new Request([], [], $server);

        $this->assertSame('/', $request->getUri());
    }
}
