<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\View;

use Laminas\EventManager\EventManager;
use Laminas\EventManager\Test\EventListenerIntrospectionTrait;
use Laminas\Mvc\MvcEvent;
use Laminas\Mvc\View\Http\CreateViewModelListener;
use Laminas\View\Model\ViewModel;
use PHPUnit\Framework\TestCase;
use stdClass;

use function gettype;

class CreateViewModelListenerTest extends TestCase
{
    use EventListenerIntrospectionTrait;

    private CreateViewModelListener $listener;
    private MvcEvent $event;

    public function setUp(): void
    {
        $this->listener = new CreateViewModelListener();
        $this->event    = new MvcEvent();
    }

    public function testReCastsAssocArrayEventResultAsViewModel(): void
    {
        $array = [
            'foo' => 'bar',
        ];
        $this->event->setResult($array);
        $this->listener->createViewModelFromArray($this->event);

        $test = $this->event->getResult();
        $this->assertInstanceOf(ViewModel::class, $test);
        $this->assertEquals($array, $test->getVariables());
    }

    public function nonAssocArrayResults(): array
    {
        return [
            [null],
            [false],
            [true],
            [0],
            [1],
            [0.00],
            [1.00],
            ['string'],
            [['foo', 'bar']],
            [new stdClass()],
        ];
    }

    /**
     * @dataProvider nonAssocArrayResults
     * @param mixed $test
     */
    public function testDoesNotCastNonAssocArrayEventResults($test): void
    {
        $this->event->setResult($test);

        $this->listener->createViewModelFromArray($this->event);

        $result = $this->event->getResult();
        $this->assertEquals(gettype($test), gettype($result));
        $this->assertEquals($test, $result);
    }

    public function testAttachesListenersAtExpectedPriority(): void
    {
        $events = new EventManager();
        $this->listener->attach($events);
        $this->assertListenerAtPriority(
            [$this->listener, 'createViewModelFromArray'],
            -80,
            MvcEvent::EVENT_DISPATCH,
            $events,
            'Did not find createViewModelFromArray listener in event list at expected priority'
        );
        $this->assertListenerAtPriority(
            [$this->listener, 'createViewModelFromNull'],
            -80,
            MvcEvent::EVENT_DISPATCH,
            $events,
            'Did not find createViewModelFromNull listener in event list at expected priority'
        );
    }

    public function testDetachesListeners(): void
    {
        $events = new EventManager();
        $this->listener->attach($events);
        $listeners = $this->getArrayOfListenersForEvent(MvcEvent::EVENT_DISPATCH, $events);
        $this->assertCount(2, $listeners);

        $this->listener->detach($events);
        $listeners = $this->getArrayOfListenersForEvent(MvcEvent::EVENT_DISPATCH, $events);
        $this->assertCount(0, $listeners);
    }

    public function testViewModelCreatesViewModelWithEmptyArray(): void
    {
        $this->event->setResult([]);
        $this->listener->createViewModelFromArray($this->event);
        $result = $this->event->getResult();
        $this->assertInstanceOf(ViewModel::class, $result);
    }

    public function testViewModelCreatesViewModelWithNullResult(): void
    {
        $this->event->setResult(null);
        $this->listener->createViewModelFromNull($this->event);
        $result = $this->event->getResult();
        $this->assertInstanceOf(ViewModel::class, $result);
    }
}
