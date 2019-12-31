<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Service;

use Interop\Container\ContainerInterface;
use Laminas\Console\Adapter\AdapterInterface;
use Laminas\Console\Charset\CharsetInterface;
use Laminas\Mvc\Service\ConsoleAdapterFactory;
use PHPUnit_Framework_TestCase as TestCase;
use stdClass;

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
