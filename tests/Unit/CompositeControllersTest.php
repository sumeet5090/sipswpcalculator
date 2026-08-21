<?php

declare(strict_types=1);

namespace Tests\Unit;

use Controllers\AdminAuthAction;
use Controllers\PageController;
use Controllers\ProcessAdminLoginAction;
use Controllers\ProcessAdminLogoutAction;
use Controllers\RenderAboutAction;
use Controllers\RenderFaqAction;
use Controllers\RenderGlossaryAction;
use Controllers\RenderPrivacyAction;
use Controllers\RenderTermsAction;
use Controllers\ShowAdminLoginAction;
use Core\Http\Request;
use Core\Http\Response;
use PHPUnit\Framework\TestCase;

class CompositeControllersTest extends TestCase
{
    public function testAdminAuthActionDelegatesToShowLoginOnGet(): void
    {
        $showLogin = $this->createMock(ShowAdminLoginAction::class);
        $processLogin = $this->createMock(ProcessAdminLoginAction::class);
        $processLogout = $this->createMock(ProcessAdminLogoutAction::class);

        $showLogin->expects($this->once())
            ->method('__invoke')
            ->willReturn(Response::html('<h1>Admin Login</h1>'));

        $processLogin->expects($this->never())->method('__invoke');

        $composite = new AdminAuthAction($showLogin, $processLogin, $processLogout);
        $response = $composite->login(new Request([], [], ['REQUEST_METHOD' => 'GET']));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('Admin Login', $response->getContent());
    }

    public function testAdminAuthActionDelegatesToProcessLoginOnPost(): void
    {
        $showLogin = $this->createMock(ShowAdminLoginAction::class);
        $processLogin = $this->createMock(ProcessAdminLoginAction::class);
        $processLogout = $this->createMock(ProcessAdminLogoutAction::class);

        $processLogin->expects($this->once())
            ->method('__invoke')
            ->willReturn(Response::redirect('/admin_insights'));

        $showLogin->expects($this->never())->method('__invoke');

        $composite = new AdminAuthAction($showLogin, $processLogin, $processLogout);
        $response = $composite->login(new Request([], [], ['REQUEST_METHOD' => 'POST']));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/admin_insights', $response->getHeader('Location'));
    }

    public function testAdminAuthActionDelegatesToProcessLogout(): void
    {
        $showLogin = $this->createMock(ShowAdminLoginAction::class);
        $processLogin = $this->createMock(ProcessAdminLoginAction::class);
        $processLogout = $this->createMock(ProcessAdminLogoutAction::class);

        $processLogout->expects($this->once())
            ->method('__invoke')
            ->willReturn(Response::redirect('/admin_insights'));

        $composite = new AdminAuthAction($showLogin, $processLogin, $processLogout);
        $response = $composite->logout();

        $this->assertSame(302, $response->getStatusCode());
    }

    public function testPageControllerDelegatesToChildActions(): void
    {
        $about = $this->createMock(RenderAboutAction::class);
        $faq = $this->createMock(RenderFaqAction::class);
        $glossary = $this->createMock(RenderGlossaryAction::class);
        $privacy = $this->createMock(RenderPrivacyAction::class);
        $terms = $this->createMock(RenderTermsAction::class);

        $about->expects($this->once())->method('__invoke')->willReturn(Response::html('about'));
        $faq->expects($this->once())->method('__invoke')->willReturn(Response::html('faq'));
        $glossary->expects($this->once())->method('__invoke')->willReturn(Response::html('glossary'));
        $privacy->expects($this->once())->method('__invoke')->willReturn(Response::html('privacy'));
        $terms->expects($this->once())->method('__invoke')->willReturn(Response::html('terms'));

        $pageController = new PageController($about, $faq, $glossary, $privacy, $terms);

        $this->assertSame('about', $pageController->about()->getContent());
        $this->assertSame('faq', $pageController->faq()->getContent());
        $this->assertSame('glossary', $pageController->glossary()->getContent());
        $this->assertSame('privacy', $pageController->privacy()->getContent());
        $this->assertSame('terms', $pageController->terms()->getContent());
    }
}
