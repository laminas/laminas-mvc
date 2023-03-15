<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\View;

use Laminas\EventManager\EventManager;
use Laminas\EventManager\Test\EventListenerIntrospectionTrait;
use Laminas\Mvc\MvcEvent;
use Laminas\Mvc\View\Http\InjectViewModelListener;
use Laminas\Router\RouteMatch;
use Laminas\View\Model\ViewModel;
use PHPUnit\Framework\TestCase;

use function count;

class InjectViewModelListenerTest extends TestCase
{
    use EventListenerIntrospectionTrait;

    private InjectViewModelListener $listener;
    private MvcEvent $event;
    private RouteMatch $routeMatch;

    protected function setUp(): void
    {
        $this->listener   = new InjectViewModelListener();
        $this->event      = new MvcEvent();
        $this->routeMatch = new RouteMatch([]);
        $this->event->setRouteMatch($this->routeMatch);
    }

    public function testReplacesEventModelWithChildModelIfChildIsMarkedTerminal(): void
    {
        $childModel = new ViewModel();
        $childModel->setTerminal(true);
        $this->event->setResult($childModel);

        $this->listener->injectViewModel($this->event);
        $this->assertSame($childModel, $this->event->getViewModel());
    }

    public function testAddsViewModelAsChildOfEventViewModelWhenChildIsNotTerminal(): void
    {
        $childModel = new ViewModel();
        $this->event->setResult($childModel);

        $this->listener->injectViewModel($this->event);
        $model = $this->event->getViewModel();
        $this->assertNotSame($childModel, $model);
        $this->assertTrue($model->hasChildren());
        $this->assertEquals(1, count($model));
        $child = false;
        foreach ($model as $child) {
            break;
        }
        $this->assertSame($childModel, $child);
    }

    public function testLackOfViewModelInResultBypassesViewModelInjection(): void
    {
        $this->assertNull($this->listener->injectViewModel($this->event));
        $this->assertNull($this->event->getResult());
        $this->assertFalse($this->event->getViewModel()->hasChildren());
    }

    public function testAttachesListenersAtExpectedPriorities(): void
    {
        $events = new EventManager();
        $this->listener->attach($events);
        $this->assertListenerAtPriority(
            [$this->listener, 'injectViewModel'],
            -100,
            MvcEvent::EVENT_DISPATCH,
            $events
        );

        $this->assertListenerAtPriority(
            [$this->listener, 'injectViewModel'],
            -100,
            MvcEvent::EVENT_DISPATCH_ERROR,
            $events
        );
    }

    public function testDetachesListeners()
    {
        $events = new EventManager();
        $this->listener->attach($events);

        $listeners = $this->getArrayOfListenersForEvent(MvcEvent::EVENT_DISPATCH, $events);
        $this->assertCount(1, $listeners);
        $listeners = $this->getArrayOfListenersForEvent(MvcEvent::EVENT_DISPATCH_ERROR, $events);
        $this->assertCount(1, $listeners);

        $this->listener->detach($events);
        $listeners = $this->getArrayOfListenersForEvent(MvcEvent::EVENT_DISPATCH, $events);
        $this->assertCount(0, $listeners);
        $listeners = $this->getArrayOfListenersForEvent(MvcEvent::EVENT_DISPATCH_ERROR, $events);
        $this->assertCount(0, $listeners);
    }
}
