<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Http\Request;
use PHPUnit\Framework\TestCase;

class RequestIpResolutionTest extends TestCase
{
    public function testCloudflareConnectingIpTakesPrecedence(): void
    {
        $request = new Request([], [], [
            'HTTP_CF_CONNECTING_IP' => '203.0.113.195',
            'HTTP_X_FORWARDED_FOR'  => '198.51.100.10, 192.0.2.1',
            'REMOTE_ADDR'           => '127.0.0.1',
        ]);

        $this->assertSame('203.0.113.195', $request->getClientIp());
    }

    public function testXForwardedForExtractsFirstValidIp(): void
    {
        $request = new Request([], [], [
            'HTTP_X_FORWARDED_FOR' => '198.51.100.42, 192.0.2.100',
            'REMOTE_ADDR'          => '127.0.0.1',
        ]);

        $this->assertSame('198.51.100.42', $request->getClientIp());
    }

    public function testXForwardedForSkipsInvalidIpAndPicksNextValidIp(): void
    {
        $request = new Request([], [], [
            'HTTP_X_FORWARDED_FOR' => 'invalid_ip_string, 198.51.100.99',
            'REMOTE_ADDR'          => '127.0.0.1',
        ]);

        $this->assertSame('198.51.100.99', $request->getClientIp());
    }

    public function testIpv6AddressResolution(): void
    {
        $request = new Request([], [], [
            'HTTP_CF_CONNECTING_IP' => '2001:db8:85a3::8a2e:370:7334',
            'REMOTE_ADDR'           => '127.0.0.1',
        ]);

        $this->assertSame('2001:db8:85a3::8a2e:370:7334', $request->getClientIp());
    }

    public function testFallbackToRemoteAddrWhenHeadersAbsent(): void
    {
        $request = new Request([], [], [
            'REMOTE_ADDR' => '192.168.1.50',
        ]);

        $this->assertSame('192.168.1.50', $request->getClientIp());
    }

    public function testFallbackToDefaultWhenAllServerVarsEmpty(): void
    {
        $request = new Request([], [], []);

        $this->assertSame('127.0.0.1', $request->getClientIp());
    }
}
