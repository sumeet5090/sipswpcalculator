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
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Services\SessionManager;

class ShowAdminDashboardActionTest extends TestCase
{
    private InsightRepository&Stub $insightRepo;
    private AdminDashboardPresenter&Stub $presenter;
    private ViewRenderer&MockObject $viewRenderer;
    private SessionManager&Stub $sessionManager;
    private ShowAdminDashboardAction $action;

    protected function setUp(): void
    {
        $this->insightRepo = $this->createStub(InsightRepository::class);
        $this->presenter = $this->createStub(AdminDashboardPresenter::class);
        $this->viewRenderer = $this->createMock(ViewRenderer::class);
        $this->sessionManager = $this->createStub(SessionManager::class);

        $this->sessionManager->method('ensureCsrfToken')->willReturn('mock_csrf_token_123');

        $this->action = new ShowAdminDashboardAction(
            $this->insightRepo,
            $this->presenter,
            $this->viewRenderer,
            $this->sessionManager
        );
    }

    public function testAuthenticatedRequestRendersDashboard(): void
    {
        $this->insightRepo->method('getDashboardData')->willReturn(['totalCalculations' => 5]);
        $this->presenter->method('formatForView')->willReturn(['totalCalculations' => 5]);

        $this->viewRenderer->expects($this->once())
            ->method('render')
            ->with('admin/dashboard', $this->callback(function (array $data) {
                return isset($data['csrf_token']) && $data['csrf_token'] === 'mock_csrf_token_123'
                    && isset($data['current_range_key']) && $data['current_range_key'] === '24h'
                    && isset($data['is_authenticated']) && $data['is_authenticated'] === true;
            }))
            ->willReturn('<html>Dashboard Overview</html>');

        $request = new Request(['range' => '24h'], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin_insights']);
        $response = ($this->action)($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('<html>Dashboard Overview</html>', $response->getBody());
    }
}
