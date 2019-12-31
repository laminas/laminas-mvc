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
use Laminas\Mvc\Application;
use Laminas\Mvc\MvcEvent;
use Laminas\Mvc\View\Http\DefaultRenderingStrategy;
use Laminas\ServiceManager\ServiceManager;
use Laminas\View\Model\ViewModel;
use Laminas\View\Renderer\PhpRenderer;
use Laminas\View\Resolver\TemplateMapResolver;
use Laminas\View\Strategy\PhpRendererStrategy;
use Laminas\View\View;
use PHPUnit_Framework_TestCase as TestCase;

class DefaultRendereringStrategyTest extends TestCase
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
        $evm = new EventManager();
        $evm->attachAggregate($this->strategy);
        $events = array(MvcEvent::EVENT_RENDER, MvcEvent::EVENT_RENDER_ERROR);

        foreach ($events as $event) {
            $listeners = $evm->getListeners($event);

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

    public function testTriggersRenderErrorEventInCaseOfRenderingException()
    {
        $resolver = new TemplateMapResolver();
        $resolver->add('exception', __DIR__ . '/_files/exception.phtml');
        $this->renderer->setResolver($resolver);

        $strategy = new PhpRendererStrategy($this->renderer);
        $this->view->getEventManager()->attach($strategy);

        $model = new ViewModel();
        $model->setTemplate('exception');
        $this->event->setViewModel($model);

        $services = new ServiceManager();
        $services->setService('Request', $this->request);
        $services->setService('Response', $this->response);
        $services->setInvokableClass('SharedEventManager', 'Laminas\EventManager\SharedEventManager');
        $services->setFactory('EventManager', function ($services) {
            $sharedEvents = $services->get('SharedEventManager');
            $events = new EventManager();
            $events->setSharedManager($sharedEvents);
            return $events;
        }, false);

        $application = new Application(array(), $services);
        $this->event->setApplication($application);

        $test = (object) array('flag' => false);
        $application->getEventManager()->attach(MvcEvent::EVENT_RENDER_ERROR, function ($e) use ($test) {
            $test->flag      = true;
            $test->error     = $e->getError();
            $test->exception = $e->getParam('exception');
        });

        $this->strategy->render($this->event);

        $this->assertTrue($test->flag);
        $this->assertEquals(Application::ERROR_EXCEPTION, $test->error);
        $this->assertInstanceOf('Exception', $test->exception);
        $this->assertContains('script', $test->exception->getMessage());
    }
}
