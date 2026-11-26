<?php

declare(strict_types=1);

namespace Core\Providers;

use Core\Container;

/**
 * Interface ServiceProviderInterface
 * Contract for modular dependency injection registration.
 */
interface ServiceProviderInterface
{
    /**
     * @param array<string, mixed> $config
     */
    public function register(Container $container, array $config = []): void;
}
