<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Container;
use Core\Exceptions\ContainerException;
use Core\Exceptions\NotFoundException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

// Dummy classes for testing reflection and bindings
interface DummyServiceInterface
{
    public function getVal(): string;
}

class DummyServiceA implements DummyServiceInterface
{
    public function getVal(): string
    {
        return 'A';
    }
}

class DummyServiceB
{
    public DummyServiceInterface $serviceA;

    public function __construct(DummyServiceInterface $serviceA)
    {
        $this->serviceA = $serviceA;
    }
}

class DummyPrimitiveNoDefault
{
    public string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}

class DummyPrimitiveWithDefault
{
    public string $name;

    public function __construct(string $name = 'default_name')
    {
        $this->name = $name;
    }
}

class DummyNoTypeNoDefault
{
    public mixed $val;

    public function __construct($val)
    {
        $this->val = $val;
    }
}

class DummyNoTypeWithDefault
{
    public mixed $val;

    public function __construct($val = 'default_untyped')
    {
        $this->val = $val;
    }
}

class DummyCircularA
{
    public DummyCircularB $b;

    public function __construct(DummyCircularB $b)
    {
        $this->b = $b;
    }
}

class DummyCircularB
{
    public DummyCircularA $a;

    public function __construct(DummyCircularA $a)
    {
        $this->a = $a;
    }
}

abstract class DummyAbstractClass
{
}

class DummyUnionType
{
    public DummyServiceA|string $dependency;

    public function __construct(DummyServiceA|string $dependency = 'default_union')
    {
        $this->dependency = $dependency;
    }
}

class DummyUnionTypeRequired
{
    public DummyServiceA|DummyServiceB $dependency;

    public function __construct(DummyServiceA|DummyServiceB $dependency)
    {
        $this->dependency = $dependency;
    }
}

class ContainerTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    public function testSelfRegistration(): void
    {
        $this->assertSame($this->container, $this->container->get(Container::class));
        $this->assertSame($this->container, $this->container->get(ContainerInterface::class));
        $this->assertTrue($this->container->has(ContainerInterface::class));
    }

    public function testTransientBinding(): void
    {
        $this->container->bind(DummyServiceInterface::class, function () {
            return new DummyServiceA();
        });

        $instance1 = $this->container->get(DummyServiceInterface::class);
        $instance2 = $this->container->get(DummyServiceInterface::class);

        $this->assertInstanceOf(DummyServiceA::class, $instance1);
        $this->assertInstanceOf(DummyServiceA::class, $instance2);
        $this->assertNotSame($instance1, $instance2);
    }

    public function testSingletonBinding(): void
    {
        $this->container->singleton(DummyServiceInterface::class, function () {
            return new DummyServiceA();
        });

        $instance1 = $this->container->get(DummyServiceInterface::class);
        $instance2 = $this->container->get(DummyServiceInterface::class);

        $this->assertInstanceOf(DummyServiceA::class, $instance1);
        $this->assertSame($instance1, $instance2);
    }

    public function testSingletonClassStringResolver(): void
    {
        $this->container->singleton(DummyServiceInterface::class, DummyServiceA::class);

        $instance = $this->container->get(DummyServiceInterface::class);
        $this->assertInstanceOf(DummyServiceA::class, $instance);
        $this->assertSame($instance, $this->container->get(DummyServiceInterface::class));
    }

    public function testInstanceBinding(): void
    {
        $obj = new DummyServiceA();
        $this->container->instance('custom_key', $obj);

        $this->assertTrue($this->container->has('custom_key'));
        $this->assertSame($obj, $this->container->get('custom_key'));
    }

    public function testLeadingBackslashNormalization(): void
    {
        $this->container->singleton(DummyServiceInterface::class, DummyServiceA::class);

        $this->assertTrue($this->container->has('\\' . DummyServiceInterface::class));
        $this->assertInstanceOf(DummyServiceA::class, $this->container->get('\\' . DummyServiceInterface::class));
    }

    public function testForgetMethod(): void
    {
        $this->container->singleton('temp_key', fn() => new DummyServiceA());
        $instance1 = $this->container->get('temp_key');

        $this->container->forget('temp_key');
        $this->assertFalse($this->container->has('temp_key'));
    }

    public function testFlushMethod(): void
    {
        $this->container->singleton('key1', fn() => new DummyServiceA());
        $this->container->get('key1');

        $this->container->flush();

        $this->assertFalse($this->container->has('key1'));
        $this->assertSame($this->container, $this->container->get(ContainerInterface::class));
    }

    public function testAutowiringWithoutConstructor(): void
    {
        $instance = $this->container->get(DummyServiceA::class);
        $this->assertInstanceOf(DummyServiceA::class, $instance);
    }

    public function testAutowiringWithDependencies(): void
    {
        $this->container->bind(DummyServiceInterface::class, DummyServiceA::class);

        /** @var DummyServiceB $instance */
        $instance = $this->container->get(DummyServiceB::class);
        $this->assertInstanceOf(DummyServiceB::class, $instance);
        $this->assertInstanceOf(DummyServiceA::class, $instance->serviceA);
    }

    public function testInterfaceThrowsExceptionIfNotBound(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage("Cannot instantiate interface " . DummyServiceInterface::class);
        $this->container->get(DummyServiceInterface::class);
    }

    public function testNonExistentClassThrowsNotFoundException(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage("Class NonExistentClass12345 does not exist.");
        $this->container->get('NonExistentClass12345');
    }

    public function testAbstractClassThrowsContainerException(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage("Class " . DummyAbstractClass::class . " is not instantiable.");
        $this->container->get(DummyAbstractClass::class);
    }

    public function testPrimitiveParameterWithoutDefaultThrows(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage("Cannot resolve primitive parameter 'name' in class " . DummyPrimitiveNoDefault::class);
        $this->container->get(DummyPrimitiveNoDefault::class);
    }

    public function testPrimitiveParameterWithDefaultSucceeds(): void
    {
        /** @var DummyPrimitiveWithDefault $instance */
        $instance = $this->container->get(DummyPrimitiveWithDefault::class);
        $this->assertInstanceOf(DummyPrimitiveWithDefault::class, $instance);
        $this->assertSame('default_name', $instance->name);
    }

    public function testUntypehintedParameterWithoutDefaultThrows(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage("Cannot resolve parameter 'val' in class " . DummyNoTypeNoDefault::class);
        $this->container->get(DummyNoTypeNoDefault::class);
    }

    public function testUntypehintedParameterWithDefaultSucceeds(): void
    {
        /** @var DummyNoTypeWithDefault $instance */
        $instance = $this->container->get(DummyNoTypeWithDefault::class);
        $this->assertInstanceOf(DummyNoTypeWithDefault::class, $instance);
        $this->assertSame('default_untyped', $instance->val);
    }

    public function testCircularDependencyDetection(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage("Circular dependency detected");
        $this->container->get(DummyCircularA::class);
    }

    public function testUnionTypeResolution(): void
    {
        /** @var DummyUnionTypeRequired $instance */
        $instance = $this->container->get(DummyUnionTypeRequired::class);
        $this->assertInstanceOf(DummyUnionTypeRequired::class, $instance);
        $this->assertInstanceOf(DummyServiceA::class, $instance->dependency);
    }

    public function testUnionTypeDefaultFallback(): void
    {
        /** @var DummyUnionType $instance */
        $instance = $this->container->get(DummyUnionType::class);
        $this->assertInstanceOf(DummyUnionType::class, $instance);
        $this->assertInstanceOf(DummyServiceA::class, $instance->dependency);
    }
}
