<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc\Service;

use Interop\Container\ContainerInterface;
use PHPUnit_Framework_TestCase as TestCase;
use stdClass;
use Zend\Console\Adapter\AdapterInterface;
use Zend\Console\Charset\CharsetInterface;
use Zend\Mvc\Service\ConsoleAdapterFactory;

class ConsoleAdapterFactoryTest extends TestCase
{
    use FactoryEnvironmentTrait;

    public function tearDown()
    {
        $this->setConsoleEnvironment(true);
    }

    public function testFactoryReturnsAnonymousObjectIfNotConsoleEnvironment()
    {
        $this->setConsoleEnvironment(false);
        $factory = new ConsoleAdapterFactory();
        $result  = $factory($this->createContainer(), 'ConsoleAdapter');
        $this->assertInstanceOf(stdClass::class, $result);
    }

    public function testFactoryCanUseAdapterFromConfiguration()
    {
        $this->setConsoleEnvironment(true);
        $adapter = $this->prophesize(AdapterInterface::class);

        $container = $this->prophesize(ContainerInterface::class);
        $container->get('config')->willReturn([
            'console' => [
                'adapter' => 'TestAdapter',
            ],
        ]);
        $container->get('TestAdapter')->will(function () use ($adapter) {
            return $adapter->reveal();
        });

        $factory = new ConsoleAdapterFactory();
        $result  = $factory($container->reveal(), 'ConsoleAdapter');
        $this->assertSame($adapter->reveal(), $result);
    }

    public function testFactoryReturnsAnonymousObjectIfConfiguredAdapterIsInvalid()
    {
        $this->setConsoleEnvironment(true);
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('config')->willReturn([
            'console' => [
                'adapter' => 'TestAdapter',
            ],
        ]);
        $container->get('TestAdapter')->willReturn([]);

        $factory = new ConsoleAdapterFactory();
        $result  = $factory($container->reveal(), 'ConsoleAdapter');
        $this->assertInstanceOf(stdClass::class, $result);
    }

    public function testFactoryWillDetectBestAdapterWhenNoneConfigured()
    {
        $this->setConsoleEnvironment(true);

        $container = $this->prophesize(ContainerInterface::class);
        $container->get('config')->willReturn([]);

        $factory = new ConsoleAdapterFactory();
        $result  = $factory($container->reveal(), 'ConsoleAdapter');
        $this->assertInstanceOf(AdapterInterface::class, $result);
    }

    public function testFactoryWillInjectCharsetIfConfigured()
    {
        $this->setConsoleEnvironment(true);

        $charset = $this->prophesize(CharsetInterface::class);

        $container = $this->prophesize(ContainerInterface::class);
        $container->get('config')->willReturn([
            'console' => [
                'charset' => 'CustomCharset',
            ],
        ]);
        $container->get('CustomCharset')->will(function () use ($charset) {
            return $charset->reveal();
        });

        $factory = new ConsoleAdapterFactory();
        $result  = $factory($container->reveal(), 'ConsoleAdapter');
        $this->assertInstanceOf(AdapterInterface::class, $result);
        $this->assertSame($charset->reveal(), $result->getCharset());
    }
}
