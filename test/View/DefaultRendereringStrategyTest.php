<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\View;

use Laminas\EventManager\Event;
use Laminas\EventManager\EventManager;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;
use Laminas\Mvc\View\Http\DefaultRenderingStrategy;
use Laminas\View\Model\ModelInterface as Model;
use Laminas\View\Model\ViewModel;
use Laminas\View\Renderer\PhpRenderer;
use Laminas\View\View;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * @category   Laminas
 * @package    Laminas_Mvc
 * @subpackage UnitTest
 */
class DefaultRenderingStrategyTest extends TestCase
{
    protected $event;
    protected $request;
    protected $response;
    protected $view;
    protected $renderer;
    protected $strategy;

    public function setUp()
    {
        $this->view     = new View();
        $this->request  = new Request();
        $this->response = new Response();
        $this->event    = new MvcEvent();
        $this->renderer = new PhpRenderer();

        $this->event->setRequest($this->request)
                    ->setResponse($this->response);

        $this->strategy = new DefaultRenderingStrategy($this->view);
    }

    public function testAttachesRendererAtExpectedPriority()
    {
        $events = new EventManager();
        $events->attachAggregate($this->strategy);
        $listeners = $events->getListeners(MvcEvent::EVENT_RENDER);

        $expectedCallback = array($this->strategy, 'render');
        $expectedPriority = -10000;
        $found            = false;
        foreach ($listeners as $listener) {
            $callback = $listener->getCallback();
            if ($callback === $expectedCallback) {
                if ($listener->getMetadatum('priority') == $expectedPriority) {
                    $found = true;
                    break;
                }
            }
        }
        $this->assertTrue($found, 'Renderer not found');
    }

    public function testCanDetachListenersFromEventManager()
    {
        $events = new EventManager();
        $events->attachAggregate($this->strategy);
        $this->assertEquals(1, count($events->getListeners(MvcEvent::EVENT_RENDER)));

        $events->detachAggregate($this->strategy);
        $this->assertEquals(0, count($events->getListeners(MvcEvent::EVENT_RENDER)));
    }

    public function testWillRenderAlternateStrategyWhenSelected()
    {
        $renderer = new TestAsset\DumbStrategy();
        $this->view->addRenderingStrategy(function ($e) use ($renderer) {
            return $renderer;
        }, 100);
        $model = new ViewModel(array('foo' => 'bar'));
        $model->setOption('template', 'content');
        $this->event->setResult($model);

        $result = $this->strategy->render($this->event);
        $this->assertSame($this->response, $result);

        $expected = sprintf('content (%s): %s', json_encode(array('template' => 'content')), json_encode(array('foo' => 'bar')));
    }

    public function testLayoutTemplateIsLayoutByDefault()
    {
        $this->assertEquals('layout', $this->strategy->getLayoutTemplate());
    }

    public function testLayoutTemplateIsMutable()
    {
        $this->strategy->setLayoutTemplate('alternate/layout');
        $this->assertEquals('alternate/layout', $this->strategy->getLayoutTemplate());
    }

    public function testBypassesRenderingIfResultIsAResponse()
    {
        $renderer = new TestAsset\DumbStrategy();
        $this->view->addRenderingStrategy(function ($e) use ($renderer) {
            return $renderer;
        }, 100);
        $model = new ViewModel(array('foo' => 'bar'));
        $model->setOption('template', 'content');
        $this->event->setViewModel($model);
        $this->event->setResult($this->response);

        $result = $this->strategy->render($this->event);
        $this->assertSame($this->response, $result);
    }
}
