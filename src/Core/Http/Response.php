<?php

declare(strict_types=1);

namespace Core\Http;

class Response
{
    private string $content;
    private int $statusCode;
    private array $headers;

    public function __construct(string $content = '', int $statusCode = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getBody(): string
    {
        return $this->content;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeader(string $name, ?string $default = null): ?string
    {
        foreach ($this->headers as $headerName => $value) {
            if (strcasecmp($headerName, $name) === 0) {
                return (string) $value;
            }
        }
        return $default;
    }

    public function send(): void
    {
        http_response_code($this->statusCode);

        $hasContentType = false;
        foreach ($this->headers as $name => $value) {
            if (strcasecmp($name, 'Content-Type') === 0) {
                $hasContentType = true;
            }
            header(sprintf('%s: %s', $name, $value));
        }

        if (!$hasContentType && $this->statusCode !== 301 && $this->statusCode !== 302 && $this->statusCode !== 204) {
            header('Content-Type: text/plain; charset=utf-8');
        }

        echo $this->content;
    }

    public static function json(array $data, int $statusCode = 200): self
    {
        return new self(
            json_encode($data, JSON_THROW_ON_ERROR),
            $statusCode,
            ['Content-Type' => 'application/json']
        );
    }

    public static function redirect(string $url, int $statusCode = 302): self
    {
        return new self('', $statusCode, ['Location' => $url]);
    }

    public static function html(string $html, int $statusCode = 200, array $headers = []): self
    {
        if (!isset($headers['Content-Type'])) {
            $headers['Content-Type'] = 'text/html; charset=utf-8';
        }
        if (!isset($headers['Cache-Control'])) {
            $headers['Cache-Control'] = 'no-cache, private';
        }

        return new self($html, $statusCode, $headers);
    }

    public static function csv(string $csvContent, string $filename = 'export.csv', int $statusCode = 200): self
    {
        $sanitized = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename) ?: 'export.csv';
        $encoded = rawurlencode($filename);

        return new self($csvContent, $statusCode, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$sanitized}\"; filename*=UTF-8''{$encoded}",
            'Content-Length' => (string) strlen($csvContent),
            'Cache-Control' => 'no-cache, must-revalidate',
            'Pragma' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
