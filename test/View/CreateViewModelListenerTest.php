<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\View;

use Laminas\EventManager\EventManager;
use Laminas\Mvc\MvcEvent;
use Laminas\Mvc\View\Http\CreateViewModelListener;
use PHPUnit_Framework_TestCase as TestCase;
use stdClass;

class CreateViewModelListenerTest extends TestCase
{
    public function setUp()
    {
        $this->listener   = new CreateViewModelListener();
        $this->event      = new MvcEvent();
    }

    public function testReCastsAssocArrayEventResultAsViewModel()
    {
        $array = [
            'foo' => 'bar',
        ];
        $this->event->setResult($array);
        $this->listener->createViewModelFromArray($this->event);

        $test = $this->event->getResult();
        $this->assertInstanceOf('Laminas\View\Model\ViewModel', $test);
        $this->assertEquals($array, $test->getVariables());
    }

    public function nonAssocArrayResults()
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
            [new stdClass],
        ];
    }

    /**
     * @dataProvider nonAssocArrayResults
     */
    public function testDoesNotCastNonAssocArrayEventResults($test)
    {
        $this->event->setResult($test);

        $this->listener->createViewModelFromArray($this->event);

        $result = $this->event->getResult();
        $this->assertEquals(gettype($test), gettype($result));
        $this->assertEquals($test, $result);
    }

    public function testAttachesListenersAtExpectedPriority()
    {
        $events = new EventManager();
        $events->attachAggregate($this->listener);
        $listeners = $events->getListeners(MvcEvent::EVENT_DISPATCH);

        $expectedArrayCallback = [$this->listener, 'createViewModelFromArray'];
        $expectedNullCallback  = [$this->listener, 'createViewModelFromNull'];
        $expectedPriority      = -80;
        $foundArray            = false;
        $foundNull             = false;
        foreach ($listeners as $listener) {
            $callback = $listener->getCallback();
            if ($callback === $expectedArrayCallback) {
                if ($listener->getMetadatum('priority') == $expectedPriority) {
                    $foundArray = true;
                }
            }
            if ($callback === $expectedNullCallback) {
                if ($listener->getMetadatum('priority') == $expectedPriority) {
                    $foundNull = true;
                }
            }
        }
        $this->assertTrue($foundArray, 'Listener FromArray not found');
        $this->assertTrue($foundNull,  'Listener FromNull not found');
    }

    public function testDetachesListeners()
    {
        $events = new EventManager();
        $events->attachAggregate($this->listener);
        $listeners = $events->getListeners(MvcEvent::EVENT_DISPATCH);
        $this->assertEquals(2, count($listeners));
        $events->detachAggregate($this->listener);
        $listeners = $events->getListeners(MvcEvent::EVENT_DISPATCH);
        $this->assertEquals(0, count($listeners));
    }

    public function testViewModelCreatesViewModelWithEmptyArray()
    {
        $this->event->setResult([]);
        $this->listener->createViewModelFromArray($this->event);
        $result = $this->event->getResult();
        $this->assertInstanceOf('Laminas\View\Model\ViewModel', $result);
    }

    public function testViewModelCreatesViewModelWithNullResult()
    {
        $this->event->setResult(null);
        $this->listener->createViewModelFromNull($this->event);
        $result = $this->event->getResult();
        $this->assertInstanceOf('Laminas\View\Model\ViewModel', $result);
    }
}
