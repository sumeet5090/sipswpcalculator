<?php
namespace Controllers;

class BlogController {
    public function index() {
        $active_page = 'resources.php';
        require_once __DIR__ . '/../Views/blog/index.php';
    }

    public function show($slug) {
        $active_page = 'resources.php';
        $slug = str_replace('.php', '', $slug);
        $file = __DIR__ . "/../Views/blog/{$slug}.php";
        if (file_exists($file)) {
            require_once $file;
        } else {
            http_response_code(404);
            echo "404 Blog Post Not Found";
        }
    }
}
