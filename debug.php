<?php
declare(strict_types=1);

/**
 * debug.php
 * Simple debug utility to verify service resolution in the DI Container.
 */

require_once __DIR__ . '/vendor/autoload.php';

echo "Autoloader loaded successfully.\n";

// Register Container/App boot dependencies manually for CLI test
$container = \Core\Container::getInstance();

$container->singleton(\Core\ContentManager::class, function () {
    return new \Core\ContentManager();
});
$container->singleton(\Core\MetaManager::class, function () {
    return new \Core\MetaManager();
});
$container->singleton(\Core\SchemaHelper::class, function () {
    return new \Core\SchemaHelper();
});
$container->singleton(\Services\GuideRenderer::class, function (\Core\Container $c) {
    return new \Services\GuideRenderer(
        $c->get(\Core\ContentManager::class),
        $c->get(\Core\MetaManager::class),
        $c->get(\Core\SchemaHelper::class)
    );
});

try {
    $controller = $container->get(\Controllers\CalculatorController::class);
    echo "CalculatorController successfully resolved from DI Container!\n";
} catch (\Throwable $e) {
    echo "Fatal Error resolving CalculatorController: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
