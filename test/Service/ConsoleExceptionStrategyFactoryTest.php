<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc\Service;

use Interop\Container\ContainerInterface;
use PHPUnit_Framework_TestCase as TestCase;
use Zend\Mvc\Service\ConsoleExceptionStrategyFactory;
use Zend\Mvc\View\Console\ExceptionStrategy;

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
