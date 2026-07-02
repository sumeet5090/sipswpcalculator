<?php
declare(strict_types=1);

if (php_sapi_name() === 'cli-server') {
    if (is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
        return false;
    }
}

spl_autoload_register(function ($class) {
    if (strpos($class, '\\') !== false) {
        $path = __DIR__ . '/src/' . str_replace('\\', '/', $class) . '.php';
        if (file_exists($path)) {
            require_once $path;
        }
    }
});

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

require_once __DIR__ . '/functions.php';

$routesConfig = require_once __DIR__ . '/src/Core/Config/routes.php';

$router = new \Core\Router();
$router->get('/', 'CalculatorController@home');
$router->post('/', 'CalculatorController@home');
$router->post('/generate-pdf', 'CalculatorController@generatePdf');

// Dynamic Calculators Registration
foreach ($routesConfig['calculators'] as $calc) {
    $slugParts = explode('-', ltrim($calc, '/'));
    $methodName = array_shift($slugParts);
    foreach ($slugParts as $part) {
        $methodName .= ucfirst($part);
    }
    
    $router->get($calc, "CalculatorController@{$methodName}");
    $router->post($calc, "CalculatorController@{$methodName}");
    $router->redirect($calc . '.php', $calc);
}

// Dynamic Pages Registration
foreach ($routesConfig['pages'] as $uri => $method) {
    $router->get($uri, "PageController@{$method}");
    $router->redirect($uri . '.php', $uri);
}

// Admin / Insight Routing
$router->get('/admin_insights', 'AdminController@insights');
$router->post('/admin_insights', 'AdminController@insights');
$router->redirect('/admin_insights.php', '/admin_insights');
$router->get('/log_insight', 'AdminController@logInsight');
$router->post('/log_insight', 'AdminController@logInsight');
$router->redirect('/log_insight.php', '/log_insight');

$router->get('/resources', 'BlogController@index');
$router->get('/resource', 'BlogController@index');
$router->get('/resource/{category}/{slug}', 'BlogController@show');

// Dynamic Blog Redirects
foreach ($routesConfig['blog_redirects'] as $slug => $target) {
    if (strpos($target, '/') !== false) {
        $router->redirect("/resource/{$slug}", "/resource/{$target}");
    } else {
        $router->redirect("/resource/{$slug}", "/resource/{$target}/{$slug}");
    }
}

// Dynamic Stubs Redirects
foreach ($routesConfig['stubs'] as $old => $new) {
    $router->redirect($old, $new);
    $router->redirect($old . '.php', $new);
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];
$router->dispatch($uri, $method);
