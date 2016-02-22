<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc\Service;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\Console\Request as ConsoleRequest;
use Zend\Http\PhpEnvironment\Request;
use Zend\Mvc\Application;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\RouteMatch;
use Zend\Mvc\Router\RouteStackInterface;
use Zend\Mvc\Service\ViewHelperManagerFactory;
use Zend\ServiceManager\ServiceManager;
use Zend\View\Helper;

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
        $this->services->setService('config', $config);
        $manager = $this->factory->createService($this->services);
        $this->assertInstanceof('Zend\View\HelperPluginManager', $manager);
        $doctype = $manager->get('doctype');
        $this->assertInstanceof('Zend\View\Helper\Doctype', $doctype);
    }

    public function testConsoleRequestsResultInSilentFailure()
    {
        $this->services->setService('config', []);
        $this->services->setService('Request', new ConsoleRequest());

        $manager = $this->factory->createService($this->services);

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
        $this->services->setService('config', [
            'view_manager' => [
                'base_path_console' => 'http://test.com'
            ]
        ]);
        $this->services->setService('Request', new ConsoleRequest());

        $manager = $this->factory->createService($this->services);

        $basePath = $manager->get('basepath');
        $this->assertEquals('http://test.com', $basePath());
    }

    public function urlHelperNames()
    {
        return [
            ['url'],
            ['Url'],
            [Helper\Url::class],
            ['zendviewhelperurl'],
        ];
    }

    /**
     * @group 71
     * @dataProvider urlHelperNames
     */
    public function testUrlHelperFactoryCanBeInvokedViaShortNameOrFullClassName($name)
    {
        $routeMatch = $this->prophesize(RouteMatch::class)->reveal();
        $mvcEvent = $this->prophesize(MvcEvent::class);
        $mvcEvent->getRouteMatch()->willReturn($routeMatch);

        $application = $this->prophesize(Application::class);
        $application->getMvcEvent()->willReturn($mvcEvent->reveal());

        $router = $this->prophesize(RouteStackInterface::class)->reveal();

        $this->services->setService('HttpRouter', $router);
        $this->services->setService('Router', $router);
        $this->services->setService('application', $application->reveal());
        $this->services->setService('config', []);

        $manager = $this->factory->createService($this->services);
        $helper = $manager->get($name);

        $this->assertAttributeSame($routeMatch, 'routeMatch', $helper, 'Route match was not injected');
        $this->assertAttributeSame($router, 'router', $helper, 'Router was not injected');
    }

    public function basePathConfiguration()
    {
        $names = ['basepath', 'basePath', 'BasePath', Helper\BasePath::class, 'zendviewhelperbasepath'];

        $configurations = [
            'console' => [[
                'config' => [
                    'view_manager' => [
                        'base_path_console' => '/foo/bar',
                    ],
                ],
            ], '/foo/bar'],

            'hard-coded' => [[
                'config' => [
                    'view_manager' => [
                        'base_path' => '/foo/baz',
                    ],
                ],
            ], '/foo/baz'],

            'request-base' => [[
                'config' => [], // fails creating plugin manager without this
                'request' => function () {
                    $request = $this->prophesize(Request::class);
                    $request->getBasePath()->willReturn('/foo/bat');
                    return $request->reveal();
                },
            ], '/foo/bat'],
        ];

        foreach ($names as $name) {
            foreach ($configurations as $testcase => $arguments) {
                array_unshift($arguments, $name);
                $testcase .= '-' . $name;
                yield $testcase => $arguments;
            }
        }
    }

    /**
     * @group 71
     * @dataProvider basePathConfiguration
     */
    public function testBasePathHelperFactoryCanBeInvokedViaShortNameOrFullClassName($name, array $services, $expected)
    {
        foreach ($services as $key => $value) {
            if (is_callable($value)) {
                $this->services->setFactory($key, $value);
                continue;
            }

            $this->services->setService($key, $value);
        }

        $plugins = $this->factory->createService($this->services);
        $helper = $plugins->get($name);
        $this->assertInstanceof(Helper\BasePath::class, $helper);
        $this->assertEquals($expected, $helper());
    }

    public function doctypeHelperNames()
    {
        return [
            ['doctype'],
            ['Doctype'],
            [Helper\Doctype::class],
            ['zendviewhelperdoctype'],
        ];
    }

    /**
     * @group 71
     * @dataProvider doctypeHelperNames
     */
    public function testDoctypeHelperFactoryCanBeInvokedViaShortNameOrFullClassName($name)
    {
        $this->services->setService('config', [
            'view_manager' => [
                'doctype' => Helper\Doctype::HTML5,
            ],
        ]);

        $plugins = $this->factory->createService($this->services);
        $helper = $plugins->get($name);
        $this->assertInstanceof(Helper\Doctype::class, $helper);
        $this->assertEquals('<!DOCTYPE html>', (string) $helper);
    }
}
