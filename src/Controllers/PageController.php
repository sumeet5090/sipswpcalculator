<?php

declare(strict_types=1);

namespace Controllers;

use Core\View;

/**
 * PageController
 * Handles simple static page rendering.
 */
class PageController
{
    public function about(): void
    {
        View::render('pages/about');
    }

    public function faq(): void
    {
        View::render('pages/faq');
    }

    public function glossary(): void
    {
        View::render('pages/glossary');
    }

    public function privacy(): void
    {
        View::render('pages/privacy');
    }

    public function resources(): void
    {
        View::render('pages/resources');
    }

    public function terms(): void
    {
        View::render('pages/terms');
    }
}
