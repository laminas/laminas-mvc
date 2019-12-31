<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\View\Console;

use Laminas\Console\Response;
use Laminas\EventManager\EventManager;
use Laminas\Mvc\Application;
use Laminas\Mvc\MvcEvent;
use Laminas\Mvc\View\Console\ExceptionStrategy;
use PHPUnit_Framework_TestCase as TestCase;

class ExceptionStrategyTest extends TestCase
{
    protected $strategy;

    public function setUp()
    {
        $this->strategy = new ExceptionStrategy();
    }

    public function testEventListeners()
    {
        $events = new EventManager();
        $events->attachAggregate($this->strategy);

        $listeners        = $events->getListeners(MvcEvent::EVENT_DISPATCH_ERROR);
        $expectedCallback = [$this->strategy, 'prepareExceptionViewModel'];
        $expectedPriority = 1;
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
        $this->assertTrue($found, 'MvcEvent::EVENT_DISPATCH_ERROR not found');


        $listeners        = $events->getListeners(MvcEvent::EVENT_RENDER_ERROR);
        $expectedCallback = [$this->strategy, 'prepareExceptionViewModel'];
        $expectedPriority = 1;
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
        $this->assertTrue($found, 'MvcEvent::EVENT_RENDER_ERROR not found');
    }

    public function testDefaultDisplayExceptions()
    {
        $this->assertTrue($this->strategy->displayExceptions(), 'displayExceptions should be true by default');
    }

    public function messageTokenProvider()
    {
        return [
            [':className', true],
            [':message', true],
            [':code', false],
            [':file', true],
            [':line', true],
            [':stack', true],
        ];
    }

    /**
     * @dataProvider messageTokenProvider
     */
    public function testMessageTokens($token, $found)
    {
        if ($found) {
            $this->assertContains($token, $this->strategy->getMessage(), sprintf('%s token not in message', $token));
        } else {
            $this->assertNotContains($token, $this->strategy->getMessage(), sprintf('%s token in message', $token));
        }
    }

    public function previousMessageTokenProvider()
    {
        return [
            [':className', true],
            [':message', true],
            [':code', false],
            [':file', true],
            [':line', true],
            [':stack', true],
            [':previous', true],
        ];
    }

    /**
     * @dataProvider previousMessageTokenProvider
     */
    public function testPreviousMessageTokens($token, $found)
    {
        if ($found) {
            $this->assertContains($token, $this->strategy->getMessage(), sprintf('%s token not in previousMessage', $token));
        } else {
            $this->assertNotContains($token, $this->strategy->getMessage(), sprintf('%s token in previousMessage', $token));
        }
    }

    public function testCanSetMessage()
    {
        $this->strategy->setMessage('something else');

        $this->assertEquals('something else', $this->strategy->getMessage());
    }

    public function testCanSetPreviousMessage()
    {
        $this->strategy->setPreviousMessage('something else');

        $this->assertEquals('something else', $this->strategy->getPreviousMessage());
    }

    public function testPrepareExceptionViewModelNoErrorInResultGetsSameResult()
    {
        $events = new EventManager();
        $events->attachAggregate($this->strategy);

        $event = new MvcEvent(MvcEvent::EVENT_DISPATCH_ERROR);

        $event->setResult('something');
        $this->assertEquals('something', $event->getResult(), 'When no error has been set on the event getResult should not be modified');
    }

    public function testPrepareExceptionViewModelResponseObjectInResultGetsSameResult()
    {
        $events = new EventManager();
        $events->attachAggregate($this->strategy);

        $event = new MvcEvent(MvcEvent::EVENT_DISPATCH_ERROR);

        $result = new Response();
        $event->setResult($result);
        $this->assertEquals($result, $event->getResult(), 'When a response object has been set on the event getResult should not be modified');
    }

    public function testPrepareExceptionViewModelErrorsThatMustGetSameResult()
    {
        $errors = [Application::ERROR_CONTROLLER_NOT_FOUND, Application::ERROR_CONTROLLER_INVALID, Application::ERROR_ROUTER_NO_MATCH];

        foreach ($errors as $error) {
            $events = new EventManager();
            $events->attachAggregate($this->strategy);

            $exception = new \Exception('some exception');
            $event = new MvcEvent(MvcEvent::EVENT_DISPATCH_ERROR, null, ['exception'=>$exception]);
            $event->setResult('something');
            $event->setError($error);

            $events->trigger($event, null, ['exception'=>$exception]);

            $this->assertEquals('something', $event->getResult(), sprintf('With an error of %s getResult should not be modified', $error));
        }
    }

    public function testPrepareExceptionViewModelErrorException()
    {
        $errors = [Application::ERROR_EXCEPTION, 'user-defined-error'];

        foreach ($errors as $error) {
            $events = new EventManager();
            $events->attachAggregate($this->strategy);

            $exception = new \Exception('message foo');
            $event = new MvcEvent(MvcEvent::EVENT_DISPATCH_ERROR, null, ['exception'=>$exception]);

            $event->setError($error);

            $this->strategy->prepareExceptionViewModel($event);

            $this->assertInstanceOf('Laminas\View\Model\ConsoleModel', $event->getResult());
            $this->assertNotEquals('something', $event->getResult()->getResult(), sprintf('With an error of %s getResult should have been modified', $error));
            $this->assertContains('message foo', $event->getResult()->getResult(), sprintf('With an error of %s getResult should have been modified', $error));
        }
    }

    public function testPrepareExceptionRendersPreviousMessages()
    {
        $events = new EventManager();
        $events->attachAggregate($this->strategy);

        $messages  = ['message foo', 'message bar', 'deepest message'];
        $exception = null;
        $i         = 0;
        do {
            $exception = new \Exception($messages[$i], null, $exception);
            $i++;
        } while ($i < count($messages));

        $event = new MvcEvent(MvcEvent::EVENT_DISPATCH_ERROR, null, ['exception'=>$exception]);
        $event->setError('user-defined-error');

        $events->trigger($event, null, ['exception'=>$exception]); //$this->strategy->prepareExceptionViewModel($event);

        foreach ($messages as $message) {
            $this->assertContains($message, $event->getResult()->getResult(), sprintf('Not all errors are rendered'));
        }
    }
}
