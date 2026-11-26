<?php

declare(strict_types=1);

namespace Core\Http;

class Request
{
    /** @var array<string, mixed> */
    private array $get;
    /** @var array<string, mixed> */
    private array $post;
    /** @var array<string, mixed> */
    private array $server;
    /** @var array<string, array<string, mixed>> */
    private array $files;
    /** @var array<string, mixed> */
    private array $cookies;

    private ?string $rawBody;

    /**
     * @param array<string, mixed> $get
     * @param array<string, mixed> $post
     * @param array<string, mixed> $server
     * @param array<string, array<string, mixed>> $files
     * @param array<string, mixed> $cookies
     */
    public function __construct(
        array $get = [],
        array $post = [],
        array $server = [],
        array $files = [],
        ?string $rawBody = null,
        array $cookies = []
    ) {
        $this->get = $get;
        $this->post = $post;
        $this->server = $server;
        $this->files = $files;
        $this->rawBody = $rawBody;
        $this->cookies = $cookies;
    }

    public static function createFromGlobals(): self
    {
        return new self($_GET, $_POST, $_SERVER, $_FILES, null, $_COOKIE);
    }

    public function getMethod(): string
    {
        return $this->server['REQUEST_METHOD'] ?? 'GET';
    }

    public function getUri(): string
    {
        $uri = (string) ($this->server['REQUEST_URI'] ?? '/');
        if ($uri === '') {
            return '/';
        }
        $position = strpos($uri, '?');
        if ($position !== false) {
            $uri = substr($uri, 0, $position);
        }
        $uri = (string) preg_replace('#/{2,}#', '/', $uri);
        return $uri !== '' ? $uri : '/';
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->get[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    public function server(string $key, mixed $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function files(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function getCookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function getCookies(): array
    {
        return $this->cookies;
    }

    public function getClientIp(): string
    {
        $remoteAddr = (string) ($this->server['REMOTE_ADDR'] ?? '127.0.0.1');
        $validRemoteAddr = filter_var($remoteAddr, FILTER_VALIDATE_IP) ? $remoteAddr : '127.0.0.1';

        $trustedProxies = \Core\Env::getArray('TRUSTED_PROXIES', []);
        $isProxyTrusted = !empty($trustedProxies) && in_array($validRemoteAddr, $trustedProxies, true);

        if (!$isProxyTrusted) {
            return $validRemoteAddr;
        }

        // Check for Cloudflare connecting IP if present and valid
        if (!empty($this->server['HTTP_CF_CONNECTING_IP'])) {
            $cfIp = trim((string) $this->server['HTTP_CF_CONNECTING_IP']);
            if (filter_var($cfIp, FILTER_VALIDATE_IP)) {
                return $cfIp;
            }
        }

        // Check for X-Forwarded-For if present
        if (!empty($this->server['HTTP_X_FORWARDED_FOR'])) {
            $rawXff = (string) $this->server['HTTP_X_FORWARDED_FOR'];
            $ips = array_map('trim', explode(',', $rawXff));
            foreach ($ips as $ip) {
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        if (!empty($this->server['HTTP_CLIENT_IP'])) {
            $clientIp = trim((string) $this->server['HTTP_CLIENT_IP']);
            if (filter_var($clientIp, FILTER_VALIDATE_IP)) {
                return $clientIp;
            }
        }

        return $validRemoteAddr;
    }

    public function isPost(): bool
    {
        return $this->getMethod() === 'POST';
    }

    public function isGet(): bool
    {
        return $this->getMethod() === 'GET';
    }

    /**
     * @return array<string, mixed>
     */
    public function getParsedBody(): array
    {
        if (!empty($this->post)) {
            return $this->post;
        }

        $json = $this->getJsonBody();
        if ($json !== null) {
            return $json;
        }

        return $this->post;
    }

    public function getRawBody(): string
    {
        if ($this->rawBody === null) {
            $this->rawBody = file_get_contents('php://input') ?: '';
        }
        return $this->rawBody;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getJsonBody(): ?array
    {
        $content = $this->getRawBody();
        if (trim($content) === '') {
            return null;
        }
        $decoded = json_decode($content, true);
        return is_array($decoded) ? $decoded : null;
    }
}
