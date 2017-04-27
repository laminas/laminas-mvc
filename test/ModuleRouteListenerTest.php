<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc;

use PHPUnit\Framework\TestCase;
use Zend\EventManager\EventManager;
use Zend\Http\PhpEnvironment\Request;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\RouteListener;
use Zend\Router;
use Zend\Router\RouteMatch;

class ModuleRouteListenerTest extends TestCase
{
    public function setUp()
    {
        $this->request             = new Request();
        $this->events              = new EventManager();
        $this->router              = new Router\Http\TreeRouteStack();
        $this->routeListener       = new RouteListener();
        $this->moduleRouteListener = new ModuleRouteListener();

        $this->routeListener->attach($this->events);
        $this->moduleRouteListener->attach($this->events, -1);
    }

    public function testRouteReturningModuleNamespaceInRouteMatchTriggersControllerRename()
    {
        $this->router->addRoute('foo', [
            'type' => 'Literal',
            'options' => [
                'route'    => '/foo',
                'defaults' => [
                    ModuleRouteListener::MODULE_NAMESPACE => 'Foo',
                    'controller' => 'Index',
                ],
            ],
        ]);
        $this->request->setUri('/foo');
        $event = new MvcEvent();
        $event->setName('route');
        $event->setRouter($this->router);
        $event->setRequest($this->request);
        $this->events->triggerEvent($event);

        $matches = $event->getRouteMatch();
        $this->assertInstanceOf(RouteMatch::class, $matches);
        $this->assertEquals('Foo\Index', $matches->getParam('controller'));
        $this->assertEquals('Index', $matches->getParam(ModuleRouteListener::ORIGINAL_CONTROLLER));
    }

    public function testRouteNotReturningModuleNamespaceInRouteMatchLeavesControllerUntouched()
    {
        $this->router->addRoute('foo', [
            'type' => 'Literal',
            'options' => [
                'route'    => '/foo',
                'defaults' => [
                    'controller' => 'Index',
                ],
            ],
        ]);
        $this->request->setUri('/foo');
        $event = new MvcEvent();
        $event->setName('route');
        $event->setRouter($this->router);
        $event->setRequest($this->request);
        $this->events->triggerEvent($event);

        $matches = $event->getRouteMatch();
        $this->assertInstanceOf(RouteMatch::class, $matches);
        $this->assertEquals('Index', $matches->getParam('controller'));
    }

    public function testMultipleRegistrationShouldNotResultInMultiplePrefixingOfControllerName()
    {
        $moduleListener = new ModuleRouteListener();
        $moduleListener->attach($this->events);

        $this->router->addRoute('foo', [
            'type' => 'Literal',
            'options' => [
                'route'    => '/foo',
                'defaults' => [
                    ModuleRouteListener::MODULE_NAMESPACE => 'Foo',
                    'controller' => 'Index',
                ],
            ],
        ]);
        $this->request->setUri('/foo');
        $event = new MvcEvent();
        $event->setName('route');
        $event->setRouter($this->router);
        $event->setRequest($this->request);
        $this->events->triggerEvent($event);

        $matches = $event->getRouteMatch();
        $this->assertInstanceOf(RouteMatch::class, $matches);
        $this->assertEquals('Foo\Index', $matches->getParam('controller'));
        $this->assertEquals('Index', $matches->getParam(ModuleRouteListener::ORIGINAL_CONTROLLER));
    }

    public function testRouteMatchIsTransformedToProperControllerClassName()
    {
        $moduleListener = new ModuleRouteListener();
        $moduleListener->attach($this->events);

        $this->router->addRoute('foo', [
            'type' => 'Literal',
            'options' => [
                'route'    => '/foo',
                'defaults' => [
                    ModuleRouteListener::MODULE_NAMESPACE => 'Foo',
                    'controller' => 'some-index',
                ],
            ],
        ]);

        $this->request->setUri('/foo');
        $event = new MvcEvent();
        $event->setName('route');
        $event->setRouter($this->router);
        $event->setRequest($this->request);
        $this->events->triggerEvent($event);

        $matches = $event->getRouteMatch();
        $this->assertInstanceOf(RouteMatch::class, $matches);
        $this->assertEquals('Foo\SomeIndex', $matches->getParam('controller'));
        $this->assertEquals('some-index', $matches->getParam(ModuleRouteListener::ORIGINAL_CONTROLLER));
    }
}
