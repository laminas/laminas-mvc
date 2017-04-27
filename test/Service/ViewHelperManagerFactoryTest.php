<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc\Service;

use PHPUnit\Framework\TestCase;
use Zend\Http\PhpEnvironment\Request;
use Zend\Mvc\Application;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Service\ViewHelperManagerFactory;
use Zend\Router\RouteMatch;
use Zend\Router\RouteStackInterface;
use Zend\ServiceManager\ServiceManager;
use Zend\View\Helper;
use Zend\View\HelperPluginManager;

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
            ['zendviewhelperurl'],
        ];
    }

    /**
     * @group 71
     * @dataProvider urlHelperNames
     */
    public function testUrlHelperFactoryCanBeInvokedViaShortNameOrFullClassName($name)
    {
        $this->markTestSkipped(sprintf(
            '%s::%s skipped until zend-view and the url() view helper are updated to use zend-router',
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
        $names = ['basepath', 'basePath', 'BasePath', Helper\BasePath::class, 'zendviewhelperbasepath'];

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

        $plugins = $this->factory->__invoke($this->services, HelperPluginManager::class);
        $helper = $plugins->get($name);
        $this->assertInstanceof(Helper\Doctype::class, $helper);
        $this->assertEquals('<!DOCTYPE html>', (string) $helper);
    }
}
