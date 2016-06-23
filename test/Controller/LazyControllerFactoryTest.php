<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc\Controller;

use Interop\Container\ContainerInterface;
use PHPUnit_Framework_TestCase as TestCase;
use Zend\Mvc\Controller\LazyControllerFactory;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\Validator\ValidatorPluginManager;

class LazyControllerFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->container = $this->prophesize(ContainerInterface::class);
    }

    public function nonClassRequestedNames()
    {
        return [
            'null'             => [null],
            'true'             => [true],
            'false'            => [false],
            'zero'             => [0],
            'int'              => [1],
            'zero-float'       => [0.0],
            'float'            => [1.1],
            'non-class-string' => ['non-class-string'],
            'array'            => [['non-class-string']],
            'object'           => [(object) ['class' => 'non-class-string']],
        ];
    }

    /**
     * @dataProvider nonClassRequestedNames
     */
    public function testCanCreateReturnsFalseForNonClassRequestedNames($requestedName)
    {
        $factory = new LazyControllerFactory();
        $this->assertFalse($factory->canCreate($this->container->reveal(), $requestedName));
    }

    public function testCanCreateReturnsFalseForClassesThatDoNotImplementDispatchableInterface()
    {
        $factory = new LazyControllerFactory();
        $this->assertFalse($factory->canCreate($this->container->reveal(), __CLASS__));
    }

    public function testFactoryInstantiatesClassDirectlyIfItHasNoConstructor()
    {
        $factory = new LazyControllerFactory();
        $controller = $factory($this->container->reveal(), TestAsset\SampleController::class);
        $this->assertInstanceOf(TestAsset\SampleController::class, $controller);
    }

    public function testFactoryInstantiatesClassDirectlyIfConstructorHasNoArguments()
    {
        $factory = new LazyControllerFactory();
        $controller = $factory($this->container->reveal(), TestAsset\ControllerWithEmptyConstructor::class);
        $this->assertInstanceOf(TestAsset\ControllerWithEmptyConstructor::class, $controller);
    }

    public function testFactoryRaisesExceptionWhenUnableToResolveATypeHintedService()
    {
        $this->container->has(TestAsset\SampleInterface::class)->willReturn(false);
        $factory = new LazyControllerFactory();
        $this->setExpectedException(
            ServiceNotFoundException::class,
            sprintf(
                'Unable to create controller "%s"; unable to resolve parameter "sample" using type hint "%s"',
                TestAsset\ControllerWithTypeHintedConstructorParameter::class,
                TestAsset\SampleInterface::class
            )
        );
        $factory($this->container->reveal(), TestAsset\ControllerWithTypeHintedConstructorParameter::class);
    }

    public function testFactoryPassesNullForScalarParameters()
    {
        $factory = new LazyControllerFactory();
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

        $factory = new LazyControllerFactory();
        $controller = $factory($this->container->reveal(), TestAsset\ControllerAcceptingConfigToConstructor::class);
        $this->assertInstanceOf(TestAsset\ControllerAcceptingConfigToConstructor::class, $controller);
        $this->assertEquals($config, $controller->config);
    }

    public function testFactoryCanInjectKnownTypeHintedServices()
    {
        $sample = $this->prophesize(TestAsset\SampleInterface::class)->reveal();
        $this->container->has(TestAsset\SampleInterface::class)->willReturn(true);
        $this->container->get(TestAsset\SampleInterface::class)->willReturn($sample);

        $factory = new LazyControllerFactory();
        $controller = $factory($this->container->reveal(), TestAsset\ControllerWithTypeHintedConstructorParameter::class);
        $this->assertInstanceOf(TestAsset\ControllerWithTypeHintedConstructorParameter::class, $controller);
        $this->assertSame($sample, $controller->sample);
    }

    public function testFactoryResolvesTypeHintsForServicesToWellKnownServiceNames()
    {
        $validators = $this->prophesize(ValidatorPluginManager::class)->reveal();
        $this->container->has('ValidatorManager')->willReturn(true);
        $this->container->get('ValidatorManager')->willReturn($validators);

        $factory = new LazyControllerFactory();
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

        $factory = new LazyControllerFactory();
        $controller = $factory($this->container->reveal(), TestAsset\ControllerWithMixedConstructorParameters::class);
        $this->assertInstanceOf(TestAsset\ControllerWithMixedConstructorParameters::class, $controller);

        $this->assertEquals($config, $controller->config);
        $this->assertNull($controller->foo);
        $this->assertEquals([], $controller->options);
        $this->assertSame($sample, $controller->sample);
        $this->assertSame($validators, $controller->validators);
    }
}
