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
use Laminas\View\Helper\BasePath;
use Laminas\View\Helper\Doctype;
use Laminas\View\Helper\Url;
use Laminas\View\HelperPluginManager;
use PHPUnit\Framework\TestCase;

use function array_unshift;
use function is_callable;
use function sprintf;

class ViewHelperManagerFactoryTest extends TestCase
{
    private ServiceManager $services;
    private ViewHelperManagerFactory $factory;

    public function setUp(): void
    {
        $this->services = new ServiceManager();
        $this->factory  = new ViewHelperManagerFactory();
    }

    /**
     * @return array
     */
    public static function emptyConfiguration()
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
        $this->assertInstanceof(Doctype::class, $doctype);
    }

    public static function urlHelperNames(): array
    {
        return [
            ['url'],
            ['Url'],
            [Url::class],
            ['laminasviewhelperurl'],
        ];
    }

    /**
     * @group 71
     * @dataProvider urlHelperNames
     */
    public function testUrlHelperFactoryCanBeInvokedViaShortNameOrFullClassName(string $name): void
    {
        $this->markTestSkipped(sprintf(
            '%s::%s skipped until laminas-view and the url() view helper are updated to use laminas-router',
            static::class,
            __FUNCTION__
        ));

        $routeMatch = $this->createMock(RouteMatch::class);
        $mvcEvent   = $this->createMock(MvcEvent::class);
        $mvcEvent->method('getRouteMatch')->willReturn($routeMatch);

        $application = $this->createMock(Application::class);
        $application->method('getMvcEvent')->willReturn($mvcEvent);

        $router = $this->createMock(RouteStackInterface::class);

        $this->services->setService('HttpRouter', $router);
        $this->services->setService('Router', $router);
        $this->services->setService('Application', $application);
        $this->services->setService('config', []);

        $manager = $this->factory->__invoke($this->services, HelperPluginManager::class);
        $helper  = $manager->get($name);

        $this->assertAttributeSame($routeMatch, 'routeMatch', $helper, 'Route match was not injected');
        $this->assertAttributeSame($router, 'router', $helper, 'Router was not injected');
    }

    public static function basePathConfiguration(): iterable
    {
        $names = ['basepath', 'basePath', 'BasePath', BasePath::class, 'laminasviewhelperbasepath'];

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
                    'Request' => function (): object {
                        $request = new Request();
                        $request->setBasePath('/foo/bat');
                        return $request;
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
     * @group 71
     * @dataProvider basePathConfiguration
     */
    public function testBasePathHelperFactoryCanBeInvokedViaShortNameOrFullClassName(
        string $name,
        array $services,
        string $expected
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
        $this->assertInstanceof(BasePath::class, $helper);
        $this->assertEquals($expected, $helper());
    }

    public static function doctypeHelperNames(): array
    {
        return [
            ['doctype'],
            ['Doctype'],
            [Doctype::class],
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
                'doctype' => Doctype::HTML5,
            ],
        ]);

        $plugins = $this->factory->__invoke($this->services, HelperPluginManager::class);
        $helper  = $plugins->get($name);
        $this->assertInstanceof(Doctype::class, $helper);
        $this->assertEquals('<!DOCTYPE html>', (string) $helper);
    }
}
