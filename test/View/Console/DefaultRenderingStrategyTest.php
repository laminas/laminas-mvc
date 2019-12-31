<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\View\Console;

use Laminas\EventManager\EventManager;
use Laminas\Mvc\MvcEvent;
use Laminas\Mvc\View\Console\DefaultRenderingStrategy;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Stdlib\Response;
use Laminas\View\Model;
use LaminasTest\Console\TestAssets\ConsoleAdapter;
use LaminasTest\ModuleManager\TestAsset\MockApplication;
use PHPUnit_Framework_TestCase as TestCase;

class DefaultRenderingStrategyTest extends TestCase
{
    protected $strategy;

    public function setUp()
    {
        $this->strategy = new DefaultRenderingStrategy();
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

    public function testIgnoresNonConsoleModelNotContainingResultKeyWhenObtainingResult()
    {
        //Register console service
        $sm = new ServiceManager();
        $sm->setService('console', new ConsoleAdapter());

        $mockApplication = new MockApplication;
        $mockApplication->setServiceManager($sm);

        $event    = new MvcEvent();
        $event->setApplication($mockApplication);

        $model    = new Model\ViewModel(array('content' => 'Page not found'));
        $response = new Response();
        $event->setResult($model);
        $event->setResponse($response);
        $this->strategy->render($event);
        $content = $response->getContent();
        $this->assertNotContains('Page not found', $content);
    }
}
