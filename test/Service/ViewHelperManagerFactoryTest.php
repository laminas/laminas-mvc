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
use ReflectionProperty;
use Zend\Console\Console;
use Zend\Console\Request as ConsoleRequest;
use Zend\Http\PhpEnvironment\Request as HttpRequest;
use Zend\Mvc\Application;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\RouteMatch;
use Zend\Mvc\Router\RouteStackInterface;
use Zend\Mvc\Service\ViewHelperManagerFactory;
use Zend\ServiceManager\ServiceManager;

class ViewHelperManagerFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->services = new ServiceManager();
        $this->factory  = new ViewHelperManagerFactory();
    }

    /**
     * @return array
     */
    public function emptyConfiguration()
    {
        return [
            'no-config'                => [[]],
            'view-manager-config-only' => [['view_manager' => []]],
            'empty-doctype-config'     => [['view_manager' => ['doctype' => null]]],
        ];
    }

    /**
     * @dataProvider emptyConfiguration
     * @param  array $config
     * @return void
     */
    public function testDoctypeFactoryDoesNotRaiseErrorOnMissingConfiguration($config)
    {
        $services = $this->services->withConfig(['services' => [
            'config' => $config,
        ]]);
        $manager = $this->factory->__invoke($services, 'ViewHelperManager');
        $this->assertInstanceof('Zend\View\HelperPluginManager', $manager);
        $doctype = $manager->get('doctype');
        $this->assertInstanceof('Zend\View\Helper\Doctype', $doctype);
    }

    public function testConsoleRequestsResultInSilentFailure()
    {
        $services = $this->services->withConfig(['services' => [
            'config'  => [],
            'Request' => new ConsoleRequest(),
        ]]);

        $manager = $this->factory->__invoke($services, 'ViewHelperManager');

        $doctype = $manager->get('doctype');
        $this->assertInstanceof('Zend\View\Helper\Doctype', $doctype);

        $basePath = $manager->get('basepath');
        $this->assertInstanceof('Zend\View\Helper\BasePath', $basePath);
    }

    /**
     * @group 6247
     */
    public function testConsoleRequestWithBasePathConsole()
    {
        // Force Console context
        $r = new ReflectionProperty(Console::class, 'isConsole');
        $r->setAccessible(true);
        $r->setValue(true);

        if (! Console::isConsole()) {
            $this->markTestSkipped('Cannot force console context; skipping test');
        }

        $services = $this->services->withConfig(['services' => [
            'config' => [
                'view_manager' => [
                    'base_path_console' => 'http://test.com',
                ],
            ],
            'Request' => new ConsoleRequest(),
        ]]);

        $this->assertTrue($services->has('config'), 'Config service does not appear to be present');
        $config = $services->get('config');
        $this->assertArrayHasKey(
            'view_manager',
            $config,
            'Config service is missing view_manager configuration'
        );
        $this->assertArrayHasKey(
            'base_path_console',
            $config['view_manager'],
            'Config service is missing base_path_console view_manager configuration'
        );

        $manager = $this->factory->__invoke($services, 'ViewHelperManager');

        $basePath = $manager->get('basepath');
        $this->assertEquals('http://test.com', $basePath());
    }

    public function testCreatesCustomUrlHelperFactory()
    {
        $routeMatch = $this->prophesize(RouteMatch::class);

        $mvcEvent = $this->prophesize(MvcEvent::class);
        $mvcEvent->getRouteMatch()->will(function () use ($routeMatch) {
            return $routeMatch->reveal();
        });

        $application = $this->prophesize(Application::class);
        $application->getMvcEvent()->will(function () use ($mvcEvent) {
            return $mvcEvent->reveal();
        });

        $router = $this->prophesize(RouteStackInterface::class);

        $container = $this->prophesize(ContainerInterface::class);
        $container->has('config')->willReturn(false);
        $container->get('Router')->will(function () use ($router) {
            return $router->reveal();
        });
        $container->get('HttpRouter')->will(function () use ($router) {
            return $router->reveal();
        });
        $container->get('application')->will(function () use ($application) {
            return $application->reveal();
        });

        $factory = $this->factory;
        $manager = $factory($container->reveal(), 'ViewHelperManager');
        $helper  = $manager->get('url');
        $this->assertAttributeSame($router->reveal(), 'router', $helper);
        $this->assertAttributeSame($routeMatch->reveal(), 'routeMatch', $helper);
    }

    public function testCustomBasePathHelperFactoryCanUseViewManagerConfig()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->has('config')->willReturn(true);
        $container->get('config')->willReturn([
            'view_manager' => [
                'base_path' => 'https://example.com/test',
            ],
        ]);

        $factory = $this->factory;
        $manager = $factory($container->reveal(), 'ViewHelperManager');
        $helper  = $manager->get('basepath');
        $this->assertEquals('https://example.com/test', $helper());
    }

    public function testCustomBasePathHelperFactoryCanUseViewManagerConsoleConfig()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->has('config')->willReturn(true);
        $container->get('config')->willReturn([
            'view_manager' => [
                'base_path_console' => 'https://example.com/test',
            ],
        ]);

        $factory = $this->factory;
        $manager = $factory($container->reveal(), 'ViewHelperManager');
        $helper  = $manager->get('basepath');
        $this->assertEquals('https://example.com/test', $helper());
    }

    public function testCustomBasePathHelperFactoryCanUseRequestService()
    {
        $request = $this->prophesize(HttpRequest::class);
        $request->getBasePath()->willReturn('https://example.com/test');

        $container = $this->prophesize(ContainerInterface::class);
        $container->has('config')->willReturn(true);
        $container->get('config')->willReturn([]);
        $container->get('Request')->will(function () use ($request) {
            return $request->reveal();
        });

        $factory = $this->factory;
        $manager = $factory($container->reveal(), 'ViewHelperManager');
        $helper  = $manager->get('basepath');
        $this->assertEquals('https://example.com/test', $helper());
    }

    public function testCustomDoctypeHelperFactoryCanUseViewManagerConfig()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->has('config')->willReturn(true);
        $container->get('config')->willReturn([
            'view_manager' => [
                'doctype' => 'CUSTOM',
            ],
        ]);

        $factory = $this->factory;
        $manager = $factory($container->reveal(), 'ViewHelperManager');
        $helper  = $manager->get('doctype');
        $this->assertEquals('CUSTOM', $helper->getDoctype());
    }
}
