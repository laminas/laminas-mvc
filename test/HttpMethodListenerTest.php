<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc;

use Laminas\Http\Request as HttpRequest;
use Laminas\Http\Response as HttpResponse;
use Laminas\Mvc\HttpMethodListener;
use Laminas\Mvc\MvcEvent;
use Laminas\Stdlib\Request;
use Laminas\Stdlib\Response;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * @covers Laminas\Mvc\HttpMethodListener
 */
class HttpMethodListenerTest extends TestCase
{
    /**
     * @var HttpMethodListener
     */
    protected $listener;

    public function setUp()
    {
        $this->listener = new HttpMethodListener();
    }

    public function testConstructor()
    {
        $methods = array('foo', 'bar');
        $listener = new HttpMethodListener(false, $methods);

        $this->assertFalse($listener->isEnabled());
        $this->assertSame(array('FOO', 'BAR'), $listener->getAllowedMethods());

        $listener = new HttpMethodListener(true, array());
        $this->assertNotEmpty($listener->getAllowedMethods());
    }

    public function testAttachesToRouteEvent()
    {
        $eventManager = $this->getMock('Laminas\EventManager\EventManagerInterface');
        $eventManager->expects($this->atLeastOnce())
                     ->method('attach')
                     ->with(MvcEvent::EVENT_ROUTE);

        $this->listener->attach($eventManager);
    }

    public function testDoesntAttachIfDisabled()
    {
        $this->listener->setEnabled(false);

        $eventManager = $this->getMock('Laminas\EventManager\EventManagerInterface');
        $eventManager->expects($this->never())
                     ->method('attach');

        $this->listener->attach($eventManager);
    }

    public function testOnRouteDoesNothingIfNotHttpEnvironment()
    {
        $event = new MvcEvent();
        $event->setRequest(new Request());

        $this->assertNull($this->listener->onRoute($event));

        $event->setRequest(new HttpRequest());
        $event->setResponse(new Response());

        $this->assertNull($this->listener->onRoute($event));
    }

    public function testOnRouteDoesNothingIfIfMethodIsAllowed()
    {
        $event = new MvcEvent();
        $request = new HttpRequest();
        $request->setMethod('foo');
        $event->setRequest($request);
        $event->setResponse(new HttpResponse());

        $this->listener->setAllowedMethods(array('foo'));

        $this->assertNull($this->listener->onRoute($event));
    }

    public function testOnRouteReturns405ResponseIfMethodNotAllowed()
    {
        $event = new MvcEvent();
        $request = new HttpRequest();
        $request->setMethod('foo');
        $event->setRequest($request);
        $event->setResponse(new HttpResponse());

        $response = $this->listener->onRoute($event);

        $this->assertInstanceOf('Laminas\Http\Response', $response);
        $this->assertSame(405, $response->getStatusCode());
    }
}
