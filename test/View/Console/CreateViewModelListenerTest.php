<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\View\Console;

use Laminas\EventManager\EventManager;
use Laminas\EventManager\Test\EventListenerIntrospectionTrait;
use Laminas\Mvc\MvcEvent;
use Laminas\Mvc\View\Console\CreateViewModelListener;
use Laminas\View\Model\ConsoleModel;
use PHPUnit_Framework_TestCase as TestCase;

class CreateViewModelListenerTest extends TestCase
{
    use EventListenerIntrospectionTrait;

    public function setUp()
    {
        $this->listener = new CreateViewModelListener();
    }

    public function testAttachesListenersAtExpectedPriorities()
    {
        $events = new EventManager();
        $this->listener->attach($events);

        $this->assertListenerAtPriority(
            [$this->listener, 'createViewModelFromString'],
            -80,
            MvcEvent::EVENT_DISPATCH,
            $events,
            'View model from string listener not found'
        );

        $this->assertListenerAtPriority(
            [$this->listener, 'createViewModelFromArray'],
            -80,
            MvcEvent::EVENT_DISPATCH,
            $events,
            'View model from array listener not found'
        );

        $this->assertListenerAtPriority(
            [$this->listener, 'createViewModelFromNull'],
            -80,
            MvcEvent::EVENT_DISPATCH,
            $events,
            'View model from null listener not found'
        );
    }

    public function testCanDetachListenersFromEventManager()
    {
        $events = new EventManager();
        $this->listener->attach($events);

        $listeners = $this->getArrayOfListenersForEvent(MvcEvent::EVENT_DISPATCH, $events);
        $this->assertCount(3, $listeners);

        $this->listener->detach($events);
        $listeners = $this->getArrayOfListenersForEvent(MvcEvent::EVENT_DISPATCH, $events);
        $this->assertCount(0, $listeners);
    }

    public function testCanCreateViewModelFromStringResult()
    {
        $event = new MvcEvent();
        $event->setResult('content');
        $this->listener->createViewModelFromString($event);

        $result = $event->getResult();
        $this->assertInstanceOf(ConsoleModel::class, $result);
        $this->assertSame('content', $result->getVariable(ConsoleModel::RESULT));
    }

    public function testCanCreateViewModelFromArrayResult()
    {
        $expected = ['foo' => 'bar'];
        $event = new MvcEvent();
        $event->setResult($expected);
        $this->listener->createViewModelFromArray($event);

        $result = $event->getResult();
        $this->assertInstanceOf(ConsoleModel::class, $result);
        $this->assertSame($expected, $result->getVariables());
    }

    public function testCanCreateViewModelFromNullResult()
    {
        $event = new MvcEvent();
        $this->listener->createViewModelFromNull($event);

        $result = $event->getResult();
        $this->assertInstanceOf(ConsoleModel::class, $result);
    }
}
