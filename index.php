<?php
declare(strict_types=1);

if (php_sapi_name() === 'cli-server') {
    if (is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
        return false;
    }
}

require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables via phpdotenv
$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

try {
    $request = \Core\Http\Request::createFromGlobals();
    $app = new \Core\App();
    $app->run($request);
} catch (\Throwable $e) {
    // If the autoloader or App container fails, this is our absolute last line of defense.
    if (class_exists('\Controllers\ErrorController')) {
        $response = \Controllers\ErrorController::handle500($e);
        $response->send();
    } else {
        http_response_code(500);
        echo "Fatal Error: " . $e->getMessage();
    }
}
