<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\ActionDispatcher;
use Core\Container;
use Core\Exceptions\ContainerException;
use Core\Exceptions\RouteNotFoundException;
use Core\Http\Request;
use Core\Http\Response;
use PHPUnit\Framework\TestCase;

class DummyActionController
{
    public function standardAction(Request $request): Response
    {
        return Response::html('standard_action_response');
    }

    public function __invoke(Request $request, string $slug = 'default_slug'): Response
    {
        return Response::html('invoked_' . $slug);
    }

    public function namedParamsAction(string $category, string $slug): Response
    {
        return Response::html("cat:{$category}|slug:{$slug}");
    }

    public function positionalParamsAction(string $a, string $b): Response
    {
        return Response::html("pos:{$a}_{$b}");
    }

    public function unionRequestAction(Request|null $req, string $val = 'ok'): Response
    {
        return Response::html("union:" . ($req !== null ? 'has_req' : 'no_req') . "_{$val}");
    }

    public function missingParamAction(string $unresolvable): Response
    {
        return Response::html($unresolvable);
    }

    public function stringReturnAction(): string
    {
        return 'string_converted_to_response';
    }

    public function nullReturnAction(): mixed
    {
        return null;
    }

    public function typedScalarAction(int $id, float $rate, bool $active): Response
    {
        return Response::html("id:{$id}|rate:{$rate}|active:" . ($active ? '1' : '0'));
    }
}

class ActionDispatcherTest extends TestCase
{
    private Container $container;
    private ActionDispatcher $dispatcher;

    protected function setUp(): void
    {
        $this->container = new Container();
        $this->container->singleton(DummyActionController::class, DummyActionController::class);
        $this->dispatcher = new ActionDispatcher($this->container);
    }

    public function testStandardActionInvocation(): void
    {
        $request = new Request([], [], ['REQUEST_URI' => '/test']);
        $response = $this->dispatcher->dispatch([DummyActionController::class, 'standardAction'], [], $request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('standard_action_response', $response->getContent());
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testInvokableControllerInvocation(): void
    {
        $request = new Request([], [], ['REQUEST_URI' => '/test']);
        $response = $this->dispatcher->dispatch([DummyActionController::class, '__invoke'], ['slug' => 'custom_slug'], $request);

        $this->assertSame('invoked_custom_slug', $response->getContent());
    }

    public function testNamedParameterResolution(): void
    {
        $request = new Request([], [], ['REQUEST_URI' => '/resource/growth/sip-basics']);
        $params = ['slug' => 'sip-basics', 'category' => 'growth'];
        $response = $this->dispatcher->dispatch([DummyActionController::class, 'namedParamsAction'], $params, $request);

        $this->assertSame('cat:growth|slug:sip-basics', $response->getContent());
    }

    public function testPositionalParameterResolution(): void
    {
        $request = new Request([], [], ['REQUEST_URI' => '/test']);
        $params = ['first', 'second'];
        $response = $this->dispatcher->dispatch([DummyActionController::class, 'positionalParamsAction'], $params, $request);

        $this->assertSame('pos:first_second', $response->getContent());
    }

    public function testUnionRequestTypeResolution(): void
    {
        $request = new Request([], [], ['REQUEST_URI' => '/test']);
        $response = $this->dispatcher->dispatch([DummyActionController::class, 'unionRequestAction'], ['val' => 'custom'], $request);

        $this->assertSame('union:has_req_custom', $response->getContent());
    }

    public function testDefaultParameterFallback(): void
    {
        $request = new Request([], [], ['REQUEST_URI' => '/test']);
        $response = $this->dispatcher->dispatch([DummyActionController::class, '__invoke'], [], $request);

        $this->assertSame('invoked_default_slug', $response->getContent());
    }

    public function testUnresolvableParameterThrowsException(): void
    {
        $request = new Request([], [], ['REQUEST_URI' => '/test']);
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage("Cannot resolve parameter 'unresolvable'");
        $this->dispatcher->dispatch([DummyActionController::class, 'missingParamAction'], [], $request);
    }

    public function testStringReturnCoercion(): void
    {
        $request = new Request([], [], ['REQUEST_URI' => '/test']);
        $response = $this->dispatcher->dispatch([DummyActionController::class, 'stringReturnAction'], [], $request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('string_converted_to_response', $response->getContent());
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testNullReturnThrowsException(): void
    {
        $request = new Request([], [], ['REQUEST_URI' => '/test']);
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage("did not return a valid Core\\Http\\Response object");
        $this->dispatcher->dispatch([DummyActionController::class, 'nullReturnAction'], [], $request);
    }

    public function testNonExistentControllerThrowsRouteNotFoundException(): void
    {
        $request = new Request([], [], ['REQUEST_URI' => '/test']);
        $this->expectException(RouteNotFoundException::class);
        $this->dispatcher->dispatch(['NonExistentController', 'action'], [], $request);
    }

    public function testNonExistentMethodThrowsRouteNotFoundException(): void
    {
        $request = new Request([], [], ['REQUEST_URI' => '/test']);
        $this->expectException(RouteNotFoundException::class);
        $this->dispatcher->dispatch([DummyActionController::class, 'nonExistentMethod'], [], $request);
    }

    public function testScalarRouteParameterCoercion(): void
    {
        $request = new Request([], [], ['REQUEST_URI' => '/test']);
        $params = ['id' => '100', 'rate' => '12.5', 'active' => 'true'];
        $response = $this->dispatcher->dispatch([DummyActionController::class, 'typedScalarAction'], $params, $request);

        $this->assertSame('id:100|rate:12.5|active:1', $response->getContent());
    }

    public function testSingleStringInvokableControllerDispatch(): void
    {
        $request = new Request([], [], ['REQUEST_URI' => '/test']);
        $response = $this->dispatcher->dispatch(DummyActionController::class, ['slug' => 'shorthand_slug'], $request);

        $this->assertSame('invoked_shorthand_slug', $response->getContent());
    }
}
