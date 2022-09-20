<?php

namespace LaminasTest\Mvc\Controller;

use Interop\Container\ContainerInterface;
use Laminas\Mvc\Controller\LazyControllerAbstractFactory;
use Laminas\Mvc\Exception\DomainException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\Validator\ValidatorPluginManager;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class LazyControllerAbstractFactoryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ContainerInterface|\Prophecy\Prophecy\ObjectProphecy
     */
    private $container;

    public function setUp(): void
    {
        $this->container = $this->prophesize(ContainerInterface::class);
    }

    public function nonClassRequestedNames()
    {
        return [
            'non-class-string' => ['non-class-string'],
        ];
    }

    /**
     * @dataProvider nonClassRequestedNames
     */
    public function testCanCreateReturnsFalseForNonClassRequestedNames($requestedName)
    {
        $factory = new LazyControllerAbstractFactory();
        $this->assertFalse($factory->canCreate($this->container->reveal(), $requestedName));
    }

    public function testCanCreateReturnsFalseForClassesThatDoNotImplementDispatchableInterface()
    {
        $factory = new LazyControllerAbstractFactory();
        $this->assertFalse($factory->canCreate($this->container->reveal(), __CLASS__));
    }

    public function testFactoryInstantiatesClassDirectlyIfItHasNoConstructor()
    {
        $factory = new LazyControllerAbstractFactory();
        $controller = $factory($this->container->reveal(), TestAsset\SampleController::class);
        $this->assertInstanceOf(TestAsset\SampleController::class, $controller);
    }

    public function testFactoryInstantiatesClassDirectlyIfConstructorHasNoArguments()
    {
        $factory = new LazyControllerAbstractFactory();
        $controller = $factory($this->container->reveal(), TestAsset\ControllerWithEmptyConstructor::class);
        $this->assertInstanceOf(TestAsset\ControllerWithEmptyConstructor::class, $controller);
    }

    public function testFactoryRaisesExceptionWhenUnableToResolveATypeHintedService()
    {
        $this->container->has(TestAsset\SampleInterface::class)->willReturn(false);
        $this->container->has(\ZendTest\Mvc\Controller\TestAsset\SampleInterface::class)->willReturn(false);
        $factory = new LazyControllerAbstractFactory();
        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Unable to create controller "%s"; unable to resolve parameter "sample" using type hint "%s"',
                TestAsset\ControllerWithTypeHintedConstructorParameter::class,
                TestAsset\SampleInterface::class
            )
        );
        $factory($this->container->reveal(), TestAsset\ControllerWithTypeHintedConstructorParameter::class);
    }

    /**
     * @requires PHP >= 8.0
     */
    public function testFactoryRaisesExceptionWhenResolvingUnionTypeHintedService(): void
    {
        $this->container->has(TestAsset\SampleInterface::class)->willReturn(false);
        $factory = new LazyControllerAbstractFactory();
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Unable to create controller "%s"; unable to resolve parameter "sample" with union type hint',
                TestAsset\ControllerWithUnionTypeHintedConstructorParameter::class
            )
        );
        $factory($this->container->reveal(), TestAsset\ControllerWithUnionTypeHintedConstructorParameter::class);
    }

    public function testFactoryPassesNullForScalarParameters()
    {
        $factory = new LazyControllerAbstractFactory();
        $controller = $factory($this->container->reveal(), TestAsset\ControllerWithScalarParameters::class);
        $this->assertInstanceOf(TestAsset\ControllerWithScalarParameters::class, $controller);
        $this->assertNull($controller->foo);
        $this->assertNull($controller->bar);
    }

    public function testFactoryInjectsConfigServiceForConfigArgumentsTypeHintedAsArray()
    {
        $config = ['foo' => 'bar'];
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn($config);

        $factory = new LazyControllerAbstractFactory();
        $controller = $factory($this->container->reveal(), TestAsset\ControllerAcceptingConfigToConstructor::class);
        $this->assertInstanceOf(TestAsset\ControllerAcceptingConfigToConstructor::class, $controller);
        $this->assertEquals($config, $controller->config);
    }

    public function testFactoryCanInjectKnownTypeHintedServices()
    {
        $sample = $this->prophesize(TestAsset\SampleInterface::class)->reveal();
        $this->container->has(TestAsset\SampleInterface::class)->willReturn(true);
        $this->container->get(TestAsset\SampleInterface::class)->willReturn($sample);

        $factory = new LazyControllerAbstractFactory();
        $controller = $factory(
            $this->container->reveal(),
            TestAsset\ControllerWithTypeHintedConstructorParameter::class
        );
        $this->assertInstanceOf(TestAsset\ControllerWithTypeHintedConstructorParameter::class, $controller);
        $this->assertSame($sample, $controller->sample);
    }

    public function testFactoryResolvesTypeHintsForServicesToWellKnownServiceNames()
    {
        $validators = $this->prophesize(ValidatorPluginManager::class)->reveal();
        $this->container->has('ValidatorManager')->willReturn(true);
        $this->container->get('ValidatorManager')->willReturn($validators);

        $factory = new LazyControllerAbstractFactory();
        $controller = $factory(
            $this->container->reveal(),
            TestAsset\ControllerAcceptingWellKnownServicesAsConstructorParameters::class
        );
        $this->assertInstanceOf(
            TestAsset\ControllerAcceptingWellKnownServicesAsConstructorParameters::class,
            $controller
        );
        $this->assertSame($validators, $controller->validators);
    }

    public function testFactoryCanSupplyAMixOfParameterTypes()
    {
        $validators = $this->prophesize(ValidatorPluginManager::class)->reveal();
        $this->container->has('ValidatorManager')->willReturn(true);
        $this->container->get('ValidatorManager')->willReturn($validators);

        $sample = $this->prophesize(TestAsset\SampleInterface::class)->reveal();
        $this->container->has(TestAsset\SampleInterface::class)->willReturn(true);
        $this->container->get(TestAsset\SampleInterface::class)->willReturn($sample);

        $config = ['foo' => 'bar'];
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn($config);

        $factory = new LazyControllerAbstractFactory();
        $controller = $factory($this->container->reveal(), TestAsset\ControllerWithMixedConstructorParameters::class);
        $this->assertInstanceOf(TestAsset\ControllerWithMixedConstructorParameters::class, $controller);

        $this->assertEquals($config, $controller->config);
        $this->assertNull($controller->foo);
        $this->assertEquals([], $controller->options);
        $this->assertSame($sample, $controller->sample);
        $this->assertSame($validators, $controller->validators);
    }
}
