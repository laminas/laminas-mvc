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
use LaminasTest\Mvc\Controller\TestAsset\ControllerLoaderAbstractFactory;
use LaminasTest\Mvc\Controller\TestAsset\UnlocatableControllerLoaderAbstractFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use stdClass;

use function var_export;

class DispatchListenerTest extends TestCase
{
    use ProphecyTrait;

    public function createMvcEvent(string $controllerMatched): MvcEvent
    {
        $routeMatch = $this->prophesize(RouteMatch::class);
        $routeMatch->getParam('controller', 'not-found')->willReturn($controllerMatched);

        $eventManager = new EventManager();

        $application = $this->prophesize(Application::class);
        $application->getEventManager()->willReturn($eventManager);

        $event = new MvcEvent();
        $event->setRequest(new Request());
        $event->setResponse(new Response());
        $event->setApplication($application->reveal());
        $event->setRouteMatch($routeMatch->reveal());

        return $event;
    }

    public function testControllerManagerUsingAbstractFactory(): void
    {
        $controllerManager = new ControllerManager(new ServiceManager(), [
            'abstract_factories' => [
                ControllerLoaderAbstractFactory::class,
            ],
        ]);
        $listener          = new DispatchListener($controllerManager);

        $event = $this->createMvcEvent('path');

        $log = [];
        $event->getApplication()->getEventManager()->attach(
            MvcEvent::EVENT_DISPATCH_ERROR,
            static function ($e) use (&$log): void {
                $log['error'] = $e->getError();
            }
        );

        $return = $listener->onDispatch($event);

        $this->assertEmpty($log, var_export($log, true));
        $this->assertSame($event->getResponse(), $return);
        $this->assertSame(200, $return->getStatusCode());
    }

    public function testUnlocatableControllerViaAbstractFactory(): void
    {
        $controllerManager = new ControllerManager(new ServiceManager(), [
            'abstract_factories' => [
                UnlocatableControllerLoaderAbstractFactory::class,
            ],
        ]);
        $listener          = new DispatchListener($controllerManager);

        $event = $this->createMvcEvent('path');

        $log = [];
        $event->getApplication()->getEventManager()->attach(
            MvcEvent::EVENT_DISPATCH_ERROR,
            static function ($e) use (&$log): void {
                $log['error'] = $e->getError();
            }
        );

        $return = $listener->onDispatch($event);

        $this->assertArrayHasKey('error', $log);
        $this->assertSame('error-controller-not-found', $log['error']);
    }

    /**
     * @dataProvider alreadySetMvcEventResultProvider
     */
    public function testWillNotDispatchWhenAnMvcEventResultIsAlreadySet(mixed $alreadySetResult): void
    {
        $event = $this->createMvcEvent('path');

        $event->setResult($alreadySetResult);

        $listener = new DispatchListener(new ControllerManager(new ServiceManager(), [
            'abstract_factories' => [
                UnlocatableControllerLoaderAbstractFactory::class,
            ],
        ]));

        $event->getApplication()->getEventManager()->attach(MvcEvent::EVENT_DISPATCH_ERROR, static function (): void {
            self::fail('No dispatch failures should be raised - dispatch should be skipped');
        });

        $listener->onDispatch($event);

        self::assertSame($alreadySetResult, $event->getResult(), 'The event result was not replaced');
    }

    /**
     * @return mixed[][]
     */
    public function alreadySetMvcEventResultProvider(): array
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
