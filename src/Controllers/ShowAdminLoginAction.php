<?php

declare(strict_types=1);

namespace Controllers;

use Core\Http\Request;
use Core\Http\Response;
use Core\ViewRenderer;
use Services\SessionManager;

/**
 * ShowAdminLoginAction
 * Single Responsibility action dedicated strictly to displaying the administrator login view.
 */
class ShowAdminLoginAction
{
    private ViewRenderer $viewRenderer;
    private SessionManager $sessionManager;

    public function __construct(
        ViewRenderer $viewRenderer,
        SessionManager $sessionManager
    ) {
        $this->viewRenderer = $viewRenderer;
        $this->sessionManager = $sessionManager;
    }

    public function __invoke(Request $request): Response
    {
        return Response::html($this->viewRenderer->render('admin/login', [
            'error' => '',
            'csrf_token' => $this->sessionManager->ensureCsrfToken(),
        ]));
    }
}
