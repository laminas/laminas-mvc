<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc\View;

use PHPUnit_Framework_TestCase as TestCase;
use stdClass;
use Zend\EventManager\EventManager;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\View\Http\CreateViewModelListener;
use ZendTest\Mvc\EventManagerIntrospectionTrait;

class CreateViewModelListenerTest extends TestCase
{
    use EventManagerIntrospectionTrait;

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
        $this->assertInstanceOf('Zend\View\Model\ViewModel', $test);
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
        $this->listener->attach($events);
        $listeners = $this->getListenersForEvent(MvcEvent::EVENT_DISPATCH, $events, true);

        $expectedArrayListener = [$this->listener, 'createViewModelFromArray'];
        $expectedNullListener  = [$this->listener, 'createViewModelFromNull'];
        $expectedPriority      = -80;
        $foundArray            = false;
        $foundNull             = false;
        foreach ($listeners as $priority => $listener) {
            if ($listener === $expectedArrayListener
                && $priority === $expectedPriority
            ) {
                $foundArray = true;
                continue;
            }

            if ($listener === $expectedNullListener
                && $priority === $expectedPriority
            ) {
                $foundNull = true;
                continue;
            }
        }
        $this->assertTrue($foundArray, 'Listener FromArray not found');
        $this->assertTrue($foundNull, 'Listener FromNull not found');
    }

    public function testDetachesListeners()
    {
        $events = new EventManager();
        $this->listener->attach($events);
        $listeners = iterator_to_array($this->getListenersForEvent(MvcEvent::EVENT_DISPATCH, $events));
        $this->assertEquals(2, count($listeners));

        $this->listener->detach($events);
        $listeners = iterator_to_array($this->getListenersForEvent(MvcEvent::EVENT_DISPATCH, $events));
        $this->assertEquals(0, count($listeners));
    }

    public function testViewModelCreatesViewModelWithEmptyArray()
    {
        $this->event->setResult([]);
        $this->listener->createViewModelFromArray($this->event);
        $result = $this->event->getResult();
        $this->assertInstanceOf('Zend\View\Model\ViewModel', $result);
    }

    public function testViewModelCreatesViewModelWithNullResult()
    {
        $this->event->setResult(null);
        $this->listener->createViewModelFromNull($this->event);
        $result = $this->event->getResult();
        $this->assertInstanceOf('Zend\View\Model\ViewModel', $result);
    }
}
