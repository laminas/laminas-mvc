<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\View;

use Laminas\EventManager\EventManager;
use Laminas\EventManager\SharedEventManager;
use Laminas\EventManager\Test\EventListenerIntrospectionTrait;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\Application;
use Laminas\Mvc\MvcEvent;
use Laminas\Mvc\View\Http\DefaultRenderingStrategy;
use Laminas\ServiceManager\Config;
use Laminas\ServiceManager\ServiceManager;
use Laminas\View\Model\ViewModel;
use Laminas\View\Renderer\PhpRenderer;
use Laminas\View\Resolver\TemplateMapResolver;
use Laminas\View\Strategy\PhpRendererStrategy;
use Laminas\View\View;
use PHPUnit\Framework\TestCase;

use function json_encode;
use function sprintf;

class DefaultRenderingStrategyTest extends TestCase
{
    use EventListenerIntrospectionTrait;

    /** @var MvcEvent */
    protected $event;
    /** @var Request */
    protected $request;
    /** @var Response */
    protected $response;
    /** @var View */
    protected $view;
    /** @var PhpRenderer */
    protected $renderer;
    /** @var DefaultRenderingStrategy */
    protected $strategy;

    public function setUp(): void
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

    public function testAttachesRendererAtExpectedPriority(): void
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

    public function testCanDetachListenersFromEventManager(): void
    {
        $events = new EventManager();
        $this->strategy->attach($events);
        $listeners = $this->getArrayOfListenersForEvent(MvcEvent::EVENT_RENDER, $events);
        $this->assertCount(1, $listeners);

        $this->strategy->detach($events);
        $listeners = $this->getArrayOfListenersForEvent(MvcEvent::EVENT_RENDER, $events);
        $this->assertCount(0, $listeners);
    }

    public function testWillRenderAlternateStrategyWhenSelected(): void
    {
        $renderer = new TestAsset\DumbStrategy();
        $this->view->addRenderingStrategy(static fn($e) => $renderer, 100);
        $model = new ViewModel(['foo' => 'bar']);
        $model->setOption('template', 'content');
        $this->event->setResult($model);

        $result = $this->strategy->render($this->event);
        $this->assertSame($this->response, $result);

        $expected = sprintf('content (%s): %s', json_encode(['template' => 'content']), json_encode(['foo' => 'bar']));
    }

    public function testLayoutTemplateIsLayoutByDefault(): void
    {
        $this->assertEquals('layout', $this->strategy->getLayoutTemplate());
    }

    public function testLayoutTemplateIsMutable(): void
    {
        $this->strategy->setLayoutTemplate('alternate/layout');
        $this->assertEquals('alternate/layout', $this->strategy->getLayoutTemplate());
    }

    public function testBypassesRenderingIfResultIsAResponse(): void
    {
        $renderer = new TestAsset\DumbStrategy();
        $this->view->addRenderingStrategy(static fn($e) => $renderer, 100);
        $model = new ViewModel(['foo' => 'bar']);
        $model->setOption('template', 'content');
        $this->event->setViewModel($model);
        $this->event->setResult($this->response);

        $result = $this->strategy->render($this->event);
        $this->assertSame($this->response, $result);
    }

    public function testTriggersRenderErrorEventInCaseOfRenderingException(): void
    {
        $this->markTestIncomplete('Test is of bad quality and requires rewrite');
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
            'factories'  => [
                'EventManager' => static function ($services): EventManager {
                    $sharedEvents = $services->get('SharedEventManager');
                    return new EventManager($sharedEvents);
                },
            ],
            'services'   => [
                'Request'  => $this->request,
                'Response' => $this->response,
            ],
            'shared'     => [
                'EventManager' => false,
            ],
        ]))->configureServiceManager($services);

        $application = new Application($services, $services->get('EventManager'), $this->request, $this->response);
        $this->event->setApplication($application);

        $test = (object) ['flag' => false];
        $application->getEventManager()->attach(MvcEvent::EVENT_RENDER_ERROR, static function ($e) use ($test): void {
            $test->flag      = true;
            $test->error     = $e->getError();
            $test->exception = $e->getParam('exception');
        });

        $this->strategy->render($this->event);

        $this->assertTrue($test->flag);
        $this->assertEquals(Application::ERROR_EXCEPTION, $test->error);
        $this->assertInstanceOf('Exception', $test->exception);
        $this->assertStringContainsString('script', $test->exception->getMessage());
    }
}
