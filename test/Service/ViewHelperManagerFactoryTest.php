<?php

declare(strict_types=1);

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

use function array_unshift;
use function is_callable;
use function sprintf;

class ViewHelperManagerFactoryTest extends TestCase
{
    public function setUp(): void
    {
        $this->services = new ServiceManager();
        $this->factory  = new ViewHelperManagerFactory();
    }

    /**
     * @return array
     */
    public function emptyConfiguration(): array
    {
        return [
            'no-config'                => [[]],
            'view-manager-config-only' => [['view_manager' => []]],
            'empty-doctype-config'     => [['view_manager' => ['doctype' => null]]],
        ];
    }

    /**
     * @dataProvider emptyConfiguration
     */
    public function testDoctypeFactoryDoesNotRaiseErrorOnMissingConfiguration(array $config): void
    {
        $this->services->setService('config', $config);
        $manager = $this->factory->__invoke($this->services, 'doctype');
        $this->assertInstanceof(HelperPluginManager::class, $manager);
        $doctype = $manager->get('doctype');
        $this->assertInstanceof(Helper\Doctype::class, $doctype);
    }

    public function urlHelperNames(): array
    {
        return [
            ['url'],
            ['Url'],
            [Helper\Url::class],
            ['laminasviewhelperurl'],
        ];
    }

    /**
     * @dataProvider urlHelperNames
     */
    public function testUrlHelperFactoryCanBeInvokedViaShortNameOrFullClassName(string $name): void
    {
        $this->markTestSkipped(sprintf(
            '%s::%s skipped until laminas-view and the url() view helper are updated to use laminas-router',
            static::class,
            __FUNCTION__
        ));

        $routeMatch = $this->prophesize(RouteMatch::class)->reveal();
        $mvcEvent   = $this->prophesize(MvcEvent::class);
        $mvcEvent->getRouteMatch()->willReturn($routeMatch);

        $application = $this->prophesize(Application::class);
        $application->getMvcEvent()->willReturn($mvcEvent->reveal());

        $router = $this->prophesize(RouteStackInterface::class)->reveal();

        $this->services->setService('HttpRouter', $router);
        $this->services->setService('Router', $router);
        $this->services->setService('Application', $application->reveal());
        $this->services->setService('config', []);

        $manager = $this->factory->__invoke($this->services, HelperPluginManager::class);
        $helper  = $manager->get($name);

        $this->assertAttributeSame($routeMatch, 'routeMatch', $helper, 'Route match was not injected');
        $this->assertAttributeSame($router, 'router', $helper, 'Router was not injected');
    }

    public function basePathConfiguration(): iterable
    {
        $names = ['basepath', 'basePath', 'BasePath', Helper\BasePath::class, 'laminasviewhelperbasepath'];

        $configurations = [
            'hard-coded'   => [
                [
                    'config' => [
                        'view_manager' => [
                            'base_path' => '/foo/baz',
                        ],
                    ],
                ],
                '/foo/baz',
            ],
            'request-base' => [
                [
                    'config'  => [], // fails creating plugin manager without this
                    'Request' => function () {
                        $request = $this->prophesize(Request::class);
                        $request->getBasePath()->willReturn('/foo/bat');
                        return $request->reveal();
                    },
                ],
                '/foo/bat',
            ],
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
     * @dataProvider basePathConfiguration
     */
    public function testBasePathHelperFactoryCanBeInvokedViaShortNameOrFullClassName(
        string $name,
        array $services,
        ?string $expected
    ): void {
        foreach ($services as $key => $value) {
            if (is_callable($value)) {
                $this->services->setFactory($key, $value);
                continue;
            }

            $this->services->setService($key, $value);
        }

        $plugins = $this->factory->__invoke($this->services, HelperPluginManager::class);
        $helper  = $plugins->get($name);
        $this->assertInstanceof(Helper\BasePath::class, $helper);
        $this->assertEquals($expected, $helper());
    }

    public function doctypeHelperNames(): array
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
    public function testDoctypeHelperFactoryCanBeInvokedViaShortNameOrFullClassName(string $name): void
    {
        $this->services->setService('config', [
            'view_manager' => [
                'doctype' => Helper\Doctype::HTML5,
            ],
        ]);

        $plugins = $this->factory->__invoke($this->services, HelperPluginManager::class);
        $helper  = $plugins->get($name);
        $this->assertInstanceof(Helper\Doctype::class, $helper);
        $this->assertEquals('<!DOCTYPE html>', (string) $helper);
    }
}
