<?php

declare(strict_types=1);

namespace Tests\Unit;

use Controllers\ShowAdminDashboardAction;
use Core\AdminAuthService;
use Core\AdminDashboardPresenter;
use Core\Http\Request;
use Core\InsightRepository;
use Core\ViewRenderer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Services\SessionManager;

class ShowAdminDashboardActionTest extends TestCase
{
    private InsightRepository&MockObject $insightRepo;
    private AdminAuthService&MockObject $authService;
    private AdminDashboardPresenter&MockObject $presenter;
    private ViewRenderer&MockObject $viewRenderer;
    private SessionManager&MockObject $sessionManager;
    private ShowAdminDashboardAction $action;

    protected function setUp(): void
    {
        $this->insightRepo = $this->createMock(InsightRepository::class);
        $this->authService = $this->createMock(AdminAuthService::class);
        $this->presenter = $this->createMock(AdminDashboardPresenter::class);
        $this->viewRenderer = $this->createMock(ViewRenderer::class);
        $this->sessionManager = $this->createMock(SessionManager::class);

        $this->sessionManager->method('ensureCsrfToken')->willReturn('mock_csrf_token_123');

        $this->action = new ShowAdminDashboardAction(
            $this->insightRepo,
            $this->authService,
            $this->presenter,
            $this->viewRenderer,
            $this->sessionManager
        );
    }

    public function testUnauthenticatedRequestRendersLoginWithCsrfToken(): void
    {
        $this->authService->method('isAuthenticated')->willReturn(false);

        $this->viewRenderer->expects($this->once())
            ->method('render')
            ->with('admin/login', [
                'error' => '',
                'csrf_token' => 'mock_csrf_token_123',
            ])
            ->willReturn('<html>Login Page with CSRF</html>');

        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin_insights']);
        $response = ($this->action)($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('<html>Login Page with CSRF</html>', $response->getBody());
    }

    public function testAuthenticatedRequestRendersDashboard(): void
    {
        $this->authService->method('isAuthenticated')->willReturn(true);
        $this->insightRepo->method('getDashboardData')->willReturn(['totalCalculations' => 5]);
        $this->presenter->method('formatForView')->willReturn(['totalCalculations' => 5]);

        $this->viewRenderer->expects($this->once())
            ->method('render')
            ->with('admin/dashboard', $this->callback(function (array $data) {
                return isset($data['csrf_token']) && $data['csrf_token'] === 'mock_csrf_token_123'
                    && isset($data['current_range_key']) && $data['current_range_key'] === '24h';
            }))
            ->willReturn('<html>Dashboard Overview</html>');

        $request = new Request(['range' => '24h'], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin_insights']);
        $response = ($this->action)($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('<html>Dashboard Overview</html>', $response->getBody());
    }
}
