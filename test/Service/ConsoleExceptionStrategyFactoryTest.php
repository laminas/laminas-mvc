<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Service;

use Interop\Container\ContainerInterface;
use Laminas\Mvc\Service\ConsoleExceptionStrategyFactory;
use Laminas\Mvc\View\Console\ExceptionStrategy;
use PHPUnit_Framework_TestCase as TestCase;

class ConsoleExceptionStrategyFactoryTest extends TestCase
{
    public function createContainer($config = [])
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->has('config')->willReturn(true);
        $container->get('config')->willReturn($config);
        return $container->reveal();
    }

    public function testReturnsExceptionStrategy()
    {
        $factory = new ConsoleExceptionStrategyFactory();
        $result  = $factory($this->createContainer(), 'ConsoleExceptionStrategy');
        $this->assertInstanceOf(ExceptionStrategy::class, $result);
    }

    public function testIfNotSetShouldUseDefaultExceptionMessageTemplate()
    {
        $factory = new ConsoleExceptionStrategyFactory();
        $result  = $factory($this->createContainer(), 'ConsoleExceptionStrategy');

        $strategy = new ExceptionStrategy();
        $this->assertEquals($strategy->getMessage(), $result->getMessage());
    }

    public function testExceptionMessageTemplateCanBeChangedInConfig()
    {
        $config = [
            'console' => [
                'view_manager' => [
                    'exception_message' => 'Custom template :className :message',
                ]
            ]
        ];
        $factory = new ConsoleExceptionStrategyFactory();
        $result  = $factory($this->createContainer($config), 'ConsoleExceptionStrategy');

        $this->assertEquals($config['console']['view_manager']['exception_message'], $result->getMessage());
    }

    public function testDisplayExceptionsEnabledByDefault()
    {
        $factory = new ConsoleExceptionStrategyFactory();
        $result  = $factory($this->createContainer(), 'ConsoleExceptionStrategy');

        $this->assertTrue($result->displayExceptions(), 'displayExceptions should be enabled by default.');
    }

    public function testDisplayExceptionsCanBeChangedInConfig()
    {
        $config = [
            'console' => [
                'view_manager' => [
                    'display_exceptions' => false,
                ]
            ]
        ];
        $factory = new ConsoleExceptionStrategyFactory();
        $result  = $factory($this->createContainer($config), 'ConsoleExceptionStrategy');

        $this->assertFalse($result->displayExceptions(), 'displayExceptions should be disabled in config.');
    }
}
