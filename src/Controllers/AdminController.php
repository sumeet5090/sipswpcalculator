<?php
namespace Controllers;

class AdminController {
    public function insights() {
        require_once __DIR__ . '/../Views/admin/admin_insights.php';
    }

    public function logInsight() {
        require_once __DIR__ . '/../Views/admin/log_insight.php';
    }
}
