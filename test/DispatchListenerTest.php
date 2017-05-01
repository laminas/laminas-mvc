<?php
/**
 * @see       https://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Mvc;

use PHPUnit\Framework\TestCase;
use Zend\EventManager\EventManager;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\Mvc\Application;
use Zend\Mvc\Controller\ControllerManager;
use Zend\Mvc\DispatchListener;
use Zend\Mvc\MvcEvent;
use Zend\Router\RouteMatch;
use Zend\ServiceManager\ServiceManager;
use Zend\Stdlib\ResponseInterface;
use Zend\View\Model\ModelInterface;

class DispatchListenerTest extends TestCase
{
    public function createMvcEvent($controllerMatched)
    {
        $response   = new Response();
        $routeMatch = $this->prophesize(RouteMatch::class);
        $routeMatch->getParam('controller', 'not-found')->willReturn('path');

        $eventManager = new EventManager();

        $application = $this->prophesize(Application::class);
        $application->getEventManager()->willReturn($eventManager);
        $application->getResponse()->willReturn($response);

        $event = new MvcEvent();
        $event->setRequest(new Request());
        $event->setResponse($response);
        $event->setApplication($application->reveal());
        $event->setRouteMatch($routeMatch->reveal());

        return $event;
    }

    public function testControllerManagerUsingAbstractFactory()
    {
        $controllerManager = new ControllerManager(new ServiceManager(), ['abstract_factories' => [
            Controller\TestAsset\ControllerLoaderAbstractFactory::class,
        ]]);
        $listener = new DispatchListener($controllerManager);

        $event = $this->createMvcEvent('path');

        $log = [];
        $event->getApplication()->getEventManager()->attach(MvcEvent::EVENT_DISPATCH_ERROR, function ($e) use (&$log) {
            $log['error'] = $e->getError();
        });

        $return = $listener->onDispatch($event);

        $this->assertEmpty($log, var_export($log, 1));
        $this->assertSame($event->getResponse(), $return);
        $this->assertSame(200, $return->getStatusCode());
    }

    public function testUnlocatableControllerViaAbstractFactory()
    {
        $controllerManager = new ControllerManager(new ServiceManager(), ['abstract_factories' => [
            Controller\TestAsset\UnlocatableControllerLoaderAbstractFactory::class,
        ]]);
        $listener = new DispatchListener($controllerManager);

        $event = $this->createMvcEvent('path');

        $log = [];
        $event->getApplication()->getEventManager()->attach(MvcEvent::EVENT_DISPATCH_ERROR, function ($e) use (&$log) {
            $log['error'] = $e->getError();
        });

        $return = $listener->onDispatch($event);

        $this->assertArrayHasKey('error', $log);
        $this->assertSame('error-controller-not-found', $log['error']);
    }

    /**
     * @dataProvider alreadySetMvcEventResultProvider
     *
     * @param mixed $alreadySetResult
     */
    public function testWillNotDispatchWhenAnMvcEventResultIsAlreadySet($alreadySetResult)
    {
        $event = $this->createMvcEvent('path');

        $event->setResult($alreadySetResult);

        $listener = new DispatchListener(new ControllerManager(new ServiceManager(), ['abstract_factories' => [
            Controller\TestAsset\UnlocatableControllerLoaderAbstractFactory::class,
        ]]));

        $event->getApplication()->getEventManager()->attach(MvcEvent::EVENT_DISPATCH_ERROR, function () {
            self::fail('No dispatch failures should be raised - dispatch should be skipped');
        });

        $listener->onDispatch($event);

        self::assertSame($alreadySetResult, $event->getResult(), 'The event result was not replaced');
    }

    /**
     * @return mixed[][]
     */
    public function alreadySetMvcEventResultProvider()
    {
        return [
            [123],
            [true],
            [false],
            [[]],
            [new \stdClass()],
            [$this],
            [$this->createMock(ModelInterface::class)],
            [$this->createMock(ResponseInterface::class)],
            [$this->createMock(Response::class)],
            [['view model data' => 'as an array']],
            [['foo' => new \stdClass()]],
            ['a response string'],
        ];
    }
}
