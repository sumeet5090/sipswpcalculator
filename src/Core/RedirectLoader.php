<?php

declare(strict_types=1);

namespace Core;

use Services\ConfigService;

/**
 * RedirectLoader
 * Encapsulates reading and parsing dynamic redirect rules from configuration.
 */
class RedirectLoader
{
    private ConfigService $configService;

    public function __construct(?ConfigService $configService = null)
    {
        $this->configService = $configService ?? new ConfigService();
    }

    /**
     * Load dynamic redirects from JSON configuration file and register them with the Router.
     *
     * @param string $redirectsPath
     * @param Router $router
     * @return void
     */
    public function loadAndRegister(string $redirectsPath, Router $router): void
    {
        $redirectsData = $this->configService->getJsonConfig($redirectsPath);

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
