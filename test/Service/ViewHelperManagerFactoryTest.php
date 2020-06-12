<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Service;

use Laminas\Http\PhpEnvironment\Request;
use Laminas\Mvc\Application;
use Laminas\Mvc\MvcEvent;
use Laminas\Mvc\Service\ViewHelperManagerFactory;
use Laminas\Router\RouteMatch;
use Laminas\Router\RouteStackInterface;
use Laminas\ServiceManager\ServiceManager;
use Laminas\View\Helper;
use Laminas\View\HelperPluginManager;
use PHPUnit\Framework\TestCase;

class ViewHelperManagerFactoryTest extends TestCase
{
    protected function setUp() : void
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
        $manager = $this->factory->__invoke($this->services, 'doctype');
        $this->assertInstanceof(HelperPluginManager::class, $manager);
        $doctype = $manager->get('doctype');
        $this->assertInstanceof(Helper\Doctype::class, $doctype);
    }

    public function urlHelperNames()
    {
        return [
            ['url'],
            ['Url'],
            [Helper\Url::class],
            ['laminasviewhelperurl'],
        ];
    }

    /**
     * @group 71
     * @dataProvider urlHelperNames
     */
    public function testUrlHelperFactoryCanBeInvokedViaShortNameOrFullClassName($name)
    {
        $this->markTestSkipped(sprintf(
            '%s::%s skipped until laminas-view and the url() view helper are updated to use laminas-router',
            get_class($this),
            __FUNCTION__
        ));

        $routeMatch = $this->prophesize(RouteMatch::class)->reveal();
        $mvcEvent = $this->prophesize(MvcEvent::class);
        $mvcEvent->getRouteMatch()->willReturn($routeMatch);

        $application = $this->prophesize(Application::class);
        $application->getMvcEvent()->willReturn($mvcEvent->reveal());

        $router = $this->prophesize(RouteStackInterface::class)->reveal();

        $this->services->setService('HttpRouter', $router);
        $this->services->setService('Router', $router);
        $this->services->setService('Application', $application->reveal());
        $this->services->setService('config', []);

        $manager = $this->factory->__invoke($this->services, HelperPluginManager::class);
        $helper = $manager->get($name);

        $this->assertAttributeSame($routeMatch, 'routeMatch', $helper, 'Route match was not injected');
        $this->assertAttributeSame($router, 'router', $helper, 'Router was not injected');
    }

    public function basePathConfiguration()
    {
        $names = ['basepath', 'basePath', 'BasePath', Helper\BasePath::class, 'laminasviewhelperbasepath'];

        $configurations = [
            'hard-coded' => [[
                'config' => [
                    'view_manager' => [
                        'base_path' => '/foo/baz',
                    ],
                ],
            ], '/foo/baz'],

            'request-base' => [[
                'config' => [], // fails creating plugin manager without this
                'Request' => function () {
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

        $plugins = $this->factory->__invoke($this->services, HelperPluginManager::class);
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
            ['laminasviewhelperdoctype'],
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

        $plugins = $this->factory->__invoke($this->services, HelperPluginManager::class);
        $helper = $plugins->get($name);
        $this->assertInstanceof(Helper\Doctype::class, $helper);
        $this->assertEquals('<!DOCTYPE html>', (string) $helper);
    }
}
