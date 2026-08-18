<?php

declare(strict_types=1);

namespace Core;

class ViteHelper
{
    private ?array $manifest = null;
    private ?bool $devServerActive = null;
    private string $environment;
    private string $devHost;
    private int $devPort;
    private ?string $manifestPath;

    public function __construct(
        string $environment = 'development',
        string $devHost = '127.0.0.1',
        int $devPort = 5173,
        ?string $manifestPath = null
    ) {
        $this->environment = $environment;
        $this->devHost = $devHost;
        $this->devPort = $devPort;
        $this->manifestPath = $manifestPath;
    }

    /**
     * Check if Vite dev server is running on configured host and port.
     */
    private function isDevServerRunning(): bool
    {
        if ($this->devServerActive !== null) {
            return $this->devServerActive;
        }

        if ($this->environment === 'production' || $this->environment === 'testing') {
            $this->devServerActive = false;
            return false;
        }

        // Fast socket check to see if dev server port is open
        $connection = @fsockopen($this->devHost, $this->devPort, $errno, $errstr, 0.05);
        if (is_resource($connection)) {
            fclose($connection);
            $this->devServerActive = true;
            return true;
        }

        $this->devServerActive = false;
        return false;
    }

    /**
     * Get the Vite asset URL (either dev server or compiled production file).
     */
    public function asset(string $entry): string
    {
        if ($this->isDevServerRunning()) {
            return "http://{$this->devHost}:{$this->devPort}/" . ltrim($entry, '/');
        }

        $this->loadManifest();
        $entryKey = ltrim($entry, '/');

        if (isset($this->manifest[$entryKey])) {
            return '/dist/' . $this->manifest[$entryKey]['file'];
        }

        return '/' . ltrim($entry, '/');
    }

    /**
     * Inject Vite Dev Server client script (only when dev server is active).
     */
    public function client(): string
    {
        if (!$this->isDevServerRunning()) {
            return '';
        }
        return "<script type=\"module\" src=\"http://{$this->devHost}:{$this->devPort}/@vite/client\"></script>";
    }

    /**
     * Get the CSS files associated with an entry.
     */
    public function css(string $entry): string
    {
        if ($this->isDevServerRunning()) {
            return ''; // CSS is injected via JS in dev mode when Vite dev server is running
        }

        $this->loadManifest();
        $entryKey = ltrim($entry, '/');
        $html = '';

        if (isset($this->manifest[$entryKey]['css'])) {
            foreach ($this->manifest[$entryKey]['css'] as $cssFile) {
                $html .= '<link rel="stylesheet" href="/dist/' . $cssFile . '">';
            }
        }

        return $html;
    }

    /**
     * Load dist/.vite/manifest.json safely.
     * Uses injected manifest path, or discovers default locations.
     */
    private function loadManifest(): void
    {
        if ($this->manifest !== null) {
            return;
        }

        if ($this->manifestPath !== null) {
            $resolved = $this->manifestPath;
        } else {
            $resolved = __DIR__ . '/../../dist/.vite/manifest.json';
            if (!file_exists($resolved)) {
                $resolved = __DIR__ . '/../../dist/manifest.json';
            }
        }

        if (file_exists($resolved)) {
            $this->manifest = json_decode((string) file_get_contents($resolved), true) ?: [];
        } else {
            $this->manifest = [];
        }
    }
}
