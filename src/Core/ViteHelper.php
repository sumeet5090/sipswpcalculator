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
        $hosts = array_unique([$this->devHost, '127.0.0.1', 'localhost']);
        foreach ($hosts as $host) {
            $connection = @fsockopen($host, $this->devPort, $errno, $errstr, 0.05);
            if (is_resource($connection)) {
                fclose($connection);
                $this->devHost = $host;
                $this->devServerActive = true;
                return true;
            }
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

        $entryData = $this->resolveManifestEntry($entry);
        $entryKey = ltrim($entry, '/');

        if ($entryData !== null && isset($entryData['file'])) {
            return '/dist/' . $entryData['file'];
        }

        if ($this->environment === 'production') {
            error_log("ViteHelper Warning: Manifest entry missing for '{$entryKey}'. Ensure 'npm run build' was executed.");
            return '';
        }

        // Development fallback when dev server is offline and no compiled manifest entry was found
        error_log("ViteHelper Warning: Vite dev server is not active on {$this->devHost}:{$this->devPort} and no manifest entry found for '{$entryKey}'. Run 'npm run dev' or 'npm run build'.");
        return '/' . $entryKey;
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

        $entryData = $this->resolveManifestEntry($entry);
        $html = '';

        if ($entryData !== null) {
            if (isset($entryData['css']) && is_array($entryData['css'])) {
                foreach ($entryData['css'] as $cssFile) {
                    $html .= '<link rel="stylesheet" href="/dist/' . $cssFile . '">';
                }
            } elseif (isset($entryData['file']) && str_ends_with($entryData['file'], '.css')) {
                $html .= '<link rel="stylesheet" href="/dist/' . $entryData['file'] . '">';
            }
        }

        return $html;
    }

    /**
     * Resolve a manifest entry by path, shorthand name, or bundled CSS association.
     * Implements Postel's Law (Robustness Principle) for asset discovery.
     */
    private function resolveManifestEntry(string $entry): ?array
    {
        $this->loadManifest();
        $key = ltrim(trim($entry), '/');

        // 1. Exact key match (e.g. 'resources/js/app.ts')
        if (isset($this->manifest[$key])) {
            return $this->manifest[$key];
        }

        // 2. Match by entry name or filename stem (e.g. 'app')
        foreach ($this->manifest as $manifestKey => $item) {
            if (isset($item['name']) && $item['name'] === $key) {
                return $item;
            }
            $stem = pathinfo($manifestKey, PATHINFO_FILENAME);
            if ($stem === $key) {
                return $item;
            }
        }

        // 3. Match direct CSS paths against entries with bundled CSS (e.g. 'resources/css/input.css' or 'input.css')
        if (str_ends_with($key, '.css')) {
            foreach ($this->manifest as $item) {
                if (!empty($item['css']) && is_array($item['css'])) {
                    return $item;
                }
            }
        }

        return null;
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
