<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Controller;

// phpcs:ignore
use Interop\Container\ContainerInterface;
use Laminas\Mvc\Controller\LazyControllerAbstractFactory;
use Laminas\Mvc\Exception\DomainException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\Validator\ValidatorPluginManager;
use LaminasTest\Mvc\Controller\TestAsset\ControllerAcceptingConfigToConstructor;
use LaminasTest\Mvc\Controller\TestAsset\ControllerAcceptingWellKnownServicesAsConstructorParameters;
use LaminasTest\Mvc\Controller\TestAsset\ControllerWithEmptyConstructor;
use LaminasTest\Mvc\Controller\TestAsset\ControllerWithMixedConstructorParameters;
use LaminasTest\Mvc\Controller\TestAsset\ControllerWithScalarParameters;
use LaminasTest\Mvc\Controller\TestAsset\ControllerWithTypeHintedConstructorParameter;
use LaminasTest\Mvc\Controller\TestAsset\ControllerWithUnionTypeHintedConstructorParameter;
use LaminasTest\Mvc\Controller\TestAsset\SampleController;
use LaminasTest\Mvc\Controller\TestAsset\SampleInterface;
use PHPUnit\Framework\TestCase;

use function sprintf;

class LazyControllerAbstractFactoryTest extends TestCase
{
    private ContainerInterface $container;

    public function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
    }

    public static function nonClassRequestedNames(): array
    {
        return [
            'non-class-string' => ['non-class-string'],
        ];
    }

    /**
     * @dataProvider nonClassRequestedNames
     */
    public function testCanCreateReturnsFalseForNonClassRequestedNames(string $requestedName): void
    {
        $factory = new LazyControllerAbstractFactory();
        self::assertFalse($factory->canCreate($this->container, $requestedName));
    }

    public function testCanCreateReturnsFalseForClassesThatDoNotImplementDispatchableInterface(): void
    {
        $factory = new LazyControllerAbstractFactory();
        self::assertFalse($factory->canCreate($this->container, self::class));
    }

    public function testFactoryInstantiatesClassDirectlyIfItHasNoConstructor(): void
    {
        $factory    = new LazyControllerAbstractFactory();
        $controller = $factory($this->container, SampleController::class);
        self::assertInstanceOf(SampleController::class, $controller);
    }

    public function testFactoryInstantiatesClassDirectlyIfConstructorHasNoArguments(): void
    {
        $factory    = new LazyControllerAbstractFactory();
        $controller = $factory($this->container, ControllerWithEmptyConstructor::class);
        self::assertInstanceOf(ControllerWithEmptyConstructor::class, $controller);
    }

    public function testFactoryRaisesExceptionWhenUnableToResolveATypeHintedService(): void
    {
        $this->container->method('has')->willReturnMap([
            [SampleInterface::class, false],
            [\ZendTest\Mvc\Controller\TestAsset\SampleInterface::class, false],
        ]);
        $factory = new LazyControllerAbstractFactory();
        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Unable to create controller "%s"; unable to resolve parameter "sample" using type hint "%s"',
                ControllerWithTypeHintedConstructorParameter::class,
                SampleInterface::class
            )
        );
        $factory($this->container, ControllerWithTypeHintedConstructorParameter::class);
    }

    /**
     * @requires PHP >= 8.0
     */
    public function testFactoryRaisesExceptionWhenResolvingUnionTypeHintedService(): void
    {
        $this->container->method('has')->willReturn(false);
        $factory = new LazyControllerAbstractFactory();
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Unable to create controller "%s"; unable to resolve parameter "sample" with union type hint',
                ControllerWithUnionTypeHintedConstructorParameter::class
            )
        );
        $factory($this->container, ControllerWithUnionTypeHintedConstructorParameter::class);
    }

    public function testFactoryPassesNullForScalarParameters(): void
    {
        $factory    = new LazyControllerAbstractFactory();
        $controller = $factory($this->container, ControllerWithScalarParameters::class);
        self::assertInstanceOf(ControllerWithScalarParameters::class, $controller);
        self::assertNull($controller->foo);
        self::assertNull($controller->bar);
    }

    public function testFactoryInjectsConfigServiceForConfigArgumentsTypeHintedAsArray(): void
    {
        $config = ['foo' => 'bar'];
        $this->container->method('has')->with('config')->willReturn(true);
        $this->container->method('get')->with('config')->willReturn($config);

        $factory    = new LazyControllerAbstractFactory();
        $controller = $factory($this->container, ControllerAcceptingConfigToConstructor::class);
        self::assertInstanceOf(ControllerAcceptingConfigToConstructor::class, $controller);
        self::assertEquals($config, $controller->config);
    }

    public function testFactoryCanInjectKnownTypeHintedServices(): void
    {
        $sample = $this->createMock(SampleInterface::class);
        $this->container->method('has')->with(SampleInterface::class)->willReturn(true);
        $this->container->method('get')->with(SampleInterface::class)->willReturn($sample);

        $factory    = new LazyControllerAbstractFactory();
        $controller = $factory(
            $this->container,
            ControllerWithTypeHintedConstructorParameter::class
        );
        self::assertInstanceOf(ControllerWithTypeHintedConstructorParameter::class, $controller);
        self::assertSame($sample, $controller->sample);
    }

    public function testFactoryResolvesTypeHintsForServicesToWellKnownServiceNames(): void
    {
        $validators = $this->createMock(ValidatorPluginManager::class);
        $this->container->method('has')->with('ValidatorManager')->willReturn(true);
        $this->container->method('get')->with('ValidatorManager')->willReturn($validators);

        $factory    = new LazyControllerAbstractFactory();
        $controller = $factory(
            $this->container,
            ControllerAcceptingWellKnownServicesAsConstructorParameters::class
        );
        self::assertInstanceOf(
            ControllerAcceptingWellKnownServicesAsConstructorParameters::class,
            $controller
        );
        self::assertSame($validators, $controller->validators);
    }

    public function testFactoryCanSupplyAMixOfParameterTypes(): void
    {
        $validators = $this->createMock(ValidatorPluginManager::class);
        $this->container->method('has')->willReturnMap([
            ['ValidatorManager', true],
            [SampleInterface::class, true],
            ['config', true],
        ]);
        $this->container->method('get')->willReturnMap([
            ['ValidatorManager', $validators],
            [SampleInterface::class, $this->createMock(SampleInterface::class)],
            ['config', ['foo' => 'bar']],
        ]);

        $factory    = new LazyControllerAbstractFactory();
        $controller = $factory($this->container, ControllerWithMixedConstructorParameters::class);
        self::assertInstanceOf(ControllerWithMixedConstructorParameters::class, $controller);

        self::assertEquals(['foo' => 'bar'], $controller->config);
        self::assertNull($controller->foo);
        self::assertEquals([], $controller->options);
        self::assertInstanceOf(SampleInterface::class, $controller->sample);
        self::assertInstanceOf(ValidatorPluginManager::class, $controller->validators);
    }
}
