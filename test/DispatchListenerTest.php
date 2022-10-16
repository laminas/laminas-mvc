<?php

declare(strict_types=1);

namespace LaminasTest\Mvc;

use Laminas\EventManager\EventManager;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\Application;
use Laminas\Mvc\Controller\ControllerManager;
use Laminas\Mvc\DispatchListener;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\RouteMatch;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Stdlib\ResponseInterface;
use Laminas\View\Model\ModelInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use stdClass;

use function var_export;

class DispatchListenerTest extends TestCase
{
    use ProphecyTrait;

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
        $controllerManager = new ControllerManager(new ServiceManager(), [
            'abstract_factories' => [
                Controller\TestAsset\ControllerLoaderAbstractFactory::class,
            ],
        ]);
        $listener          = new DispatchListener($controllerManager);

        $event = $this->createMvcEvent('path');

        $log = [];
        $event->getApplication()->getEventManager()->attach(MvcEvent::EVENT_DISPATCH_ERROR, function ($e) use (&$log) {
            $log['error'] = $e->getError();
        });

        $return = $listener->onDispatch($event);

        $this->assertEmpty($log, var_export($log, true));
        $this->assertSame($event->getResponse(), $return);
        $this->assertSame(200, $return->getStatusCode());
    }

    public function testUnlocatableControllerViaAbstractFactory()
    {
        $controllerManager = new ControllerManager(new ServiceManager(), [
            'abstract_factories' => [
                Controller\TestAsset\UnlocatableControllerLoaderAbstractFactory::class,
            ],
        ]);
        $listener          = new DispatchListener($controllerManager);

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
     * @param mixed $alreadySetResult
     */
    public function testWillNotDispatchWhenAnMvcEventResultIsAlreadySet($alreadySetResult)
    {
        $event = $this->createMvcEvent('path');

        $event->setResult($alreadySetResult);

        $listener = new DispatchListener(new ControllerManager(new ServiceManager(), [
            'abstract_factories' => [
                Controller\TestAsset\UnlocatableControllerLoaderAbstractFactory::class,
            ],
        ]));

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
            [new stdClass()],
            [$this],
            [$this->createMock(ModelInterface::class)],
            [$this->createMock(ResponseInterface::class)],
            [$this->createMock(Response::class)],
            [['view model data' => 'as an array']],
            [['foo' => new stdClass()]],
            ['a response string'],
        ];
    }
}
