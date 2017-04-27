<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc\View;

use PHPUnit\Framework\TestCase;
use Zend\EventManager\Event;
use Zend\EventManager\EventManager;
use Zend\EventManager\SharedEventManager;
use Zend\EventManager\Test\EventListenerIntrospectionTrait;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\Mvc\Application;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\View\Http\DefaultRenderingStrategy;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceManager;
use Zend\View\Renderer\PhpRenderer;
use Zend\View\View;
use Zend\View\Model\ViewModel;
use Zend\View\Resolver\TemplateMapResolver;
use Zend\View\Strategy\PhpRendererStrategy;

class DefaultRendereringStrategyTest extends TestCase
{
    use EventListenerIntrospectionTrait;

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
        $this->strategy->attach($evm);
        $events = [MvcEvent::EVENT_RENDER, MvcEvent::EVENT_RENDER_ERROR];

        foreach ($events as $event) {
            $this->assertListenerAtPriority(
                [$this->strategy, 'render'],
                -10000,
                $event,
                $evm,
                'Renderer not found'
            );
        }
    }

    public function testCanDetachListenersFromEventManager()
    {
        $events = new EventManager();
        $this->strategy->attach($events);
        $listeners = $this->getArrayOfListenersForEvent(MvcEvent::EVENT_RENDER, $events);
        $this->assertCount(1, $listeners);

        $this->strategy->detach($events);
        $listeners = $this->getArrayOfListenersForEvent(MvcEvent::EVENT_RENDER, $events);
        $this->assertCount(0, $listeners);
    }

    public function testWillRenderAlternateStrategyWhenSelected()
    {
        $renderer = new TestAsset\DumbStrategy();
        $this->view->addRenderingStrategy(function ($e) use ($renderer) {
            return $renderer;
        }, 100);
        $model = new ViewModel(['foo' => 'bar']);
        $model->setOption('template', 'content');
        $this->event->setResult($model);

        $result = $this->strategy->render($this->event);
        $this->assertSame($this->response, $result);

        $expected = sprintf('content (%s): %s', json_encode(['template' => 'content']), json_encode(['foo' => 'bar']));
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
        $model = new ViewModel(['foo' => 'bar']);
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
        $strategy->attach($this->view->getEventManager());

        $model = new ViewModel();
        $model->setTemplate('exception');
        $this->event->setViewModel($model);

        $services = new ServiceManager();
        (new Config([
            'invokables' => [
                'SharedEventManager' => SharedEventManager::class,
            ],
            'factories' => [
                'EventManager' => function ($services) {
                    $sharedEvents = $services->get('SharedEventManager');
                    $events = new EventManager($sharedEvents);
                    return $events;
                },
            ],
            'services' => [
                'Request'  => $this->request,
                'Response' => $this->response,
            ],
            'shared' => [
                'EventManager' => false,
            ],
        ]))->configureServiceManager($services);

        $application = new Application($services, $services->get('EventManager'), $this->request, $this->response);
        $this->event->setApplication($application);

        $test = (object) ['flag' => false];
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
