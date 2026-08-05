<?php

declare(strict_types=1);

namespace Core\Http;

class Request
{
    private array $get;
    private array $post;
    private array $server;
    private array $files;

    public function __construct(array $get = [], array $post = [], array $server = [], array $files = [])
    {
        $this->get = $get;
        $this->post = $post;
        $this->server = $server;
        $this->files = $files;
    }

    public static function createFromGlobals(): self
    {
        return new self($_GET, $_POST, $_SERVER, $_FILES);
    }

    public function getMethod(): string
    {
        return $this->server['REQUEST_METHOD'] ?? 'GET';
    }

    public function getUri(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        $position = strpos($uri, '?');
        if ($position !== false) {
            return substr($uri, 0, $position);
        }
        return $uri;
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

    public function files(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function isPost(): bool
    {
        return $this->getMethod() === 'POST';
    }

    public function getParsedBody(): array
    {
        return $this->post;
    }
}
