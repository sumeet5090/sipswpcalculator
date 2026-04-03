<?php
namespace Controllers;

class PageController {
    public function about() {
        require_once __DIR__ . '/../Views/pages/about.php';
    }

    public function faq() {
        require_once __DIR__ . '/../Views/pages/faq.php';
    }

    public function glossary() {
        require_once __DIR__ . '/../Views/pages/glossary.php';
    }

    public function privacy() {
        require_once __DIR__ . '/../Views/pages/privacy.php';
    }

    public function resources() {
        require_once __DIR__ . '/../Views/pages/resources.php';
    }

    public function terms() {
        require_once __DIR__ . '/../Views/pages/terms.php';
    }
}
