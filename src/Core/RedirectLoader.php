<?php

declare(strict_types=1);

namespace Core;

/**
 * RedirectLoader
 * Encapsulates reading and parsing dynamic redirect rules from disk configuration.
 */
class RedirectLoader
{
    /**
     * Load dynamic redirects from JSON configuration file and register them with the Router.
     *
     * @param string $redirectsPath
     * @param Router $router
     * @return void
     */
    public function loadAndRegister(string $redirectsPath, Router $router): void
    {
        if (!file_exists($redirectsPath)) {
            return;
        }

        $rawJson = (string) file_get_contents($redirectsPath);
        $redirectsData = json_decode($rawJson, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($redirectsData)) {
            error_log("Failed to parse content/redirects.json: " . json_last_error_msg());
            return;
        }

        if (isset($redirectsData['blog_redirects']) && is_array($redirectsData['blog_redirects'])) {
            foreach ($redirectsData['blog_redirects'] as $slug => $target) {
                if ($slug !== $target) {
                    $router->redirect("/resource/{$slug}", "/resource/{$target}");
                }
            }
        }

        if (isset($redirectsData['stubs']) && is_array($redirectsData['stubs'])) {
            foreach ($redirectsData['stubs'] as $old => $new) {
                if ($old !== $new) {
                    $router->redirect($old, $new);
                    if (substr_count($old, '/') <= 1) {
                        $router->redirect($old . '.php', $new);
                    }
                }
            }
        }
    }
}
