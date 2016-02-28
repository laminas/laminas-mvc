<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc\View\Console;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\Console\Response;
use Zend\EventManager\EventManager;
use Zend\EventManager\Test\EventListenerIntrospectionTrait;
use Zend\Mvc\Application;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\View\Console\ExceptionStrategy;

class ExceptionStrategyTest extends TestCase
{
    use EventListenerIntrospectionTrait;

    protected $strategy;

    public function setUp()
    {
        $this->strategy = new ExceptionStrategy();
    }

    public function testEventListeners()
    {
        $events = new EventManager();
        $this->strategy->attach($events);

        $this->assertListenerAtPriority(
            [$this->strategy, 'prepareExceptionViewModel'],
            1,
            MvcEvent::EVENT_DISPATCH_ERROR,
            $events,
            'MvcEvent::EVENT_DISPATCH_ERROR listener not found'
        );

        $this->assertListenerAtPriority(
            [$this->strategy, 'prepareExceptionViewModel'],
            1,
            MvcEvent::EVENT_RENDER_ERROR,
            $events,
            'MvcEvent::EVENT_RENDER_ERROR listener not found'
        );
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
        $event = new MvcEvent(MvcEvent::EVENT_DISPATCH_ERROR);

        $event->setResult('something');
        $this->assertEquals('something', $event->getResult(), 'When no error has been set on the event getResult should not be modified');
    }

    public function testPrepareExceptionViewModelResponseObjectInResultGetsSameResult()
    {
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
            $this->strategy->attach($events);

            $exception = new \Exception('some exception');
            $event = new MvcEvent(MvcEvent::EVENT_DISPATCH_ERROR, null, ['exception'=>$exception]);
            $event->setResult('something');
            $event->setError($error);
            $event->setParams(['exception' => $exception]);

            $events->triggerEvent($event);

            $this->assertEquals('something', $event->getResult(), sprintf('With an error of %s getResult should not be modified', $error));
        }
    }

    public function testPrepareExceptionViewModelErrorException()
    {
        $errors = [Application::ERROR_EXCEPTION, 'user-defined-error'];

        foreach ($errors as $error) {
            $events = new EventManager();
            $this->strategy->attach($events);

            $exception = new \Exception('message foo');
            $event = new MvcEvent(MvcEvent::EVENT_DISPATCH_ERROR, null, ['exception' => $exception]);

            $event->setError($error);

            $this->strategy->prepareExceptionViewModel($event);

            $this->assertInstanceOf('Zend\View\Model\ConsoleModel', $event->getResult());
            $this->assertNotEquals('something', $event->getResult()->getResult(), sprintf('With an error of %s getResult should have been modified', $error));
            $this->assertContains('message foo', $event->getResult()->getResult(), sprintf('With an error of %s getResult should have been modified', $error));
        }
    }

    public function testPrepareExceptionRendersPreviousMessages()
    {
        $events = new EventManager();
        $this->strategy->attach($events);

        $messages  = ['message foo', 'message bar', 'deepest message'];
        $exception = null;
        $i         = 0;
        do {
            $exception = new \Exception($messages[$i], null, $exception);
            $i++;
        } while ($i < count($messages));

        $event = new MvcEvent(MvcEvent::EVENT_DISPATCH_ERROR, null, ['exception'=>$exception]);
        $event->setError('user-defined-error');

        $events->triggerEvent($event); //$this->strategy->prepareExceptionViewModel($event);

        foreach ($messages as $message) {
            $this->assertContains($message, $event->getResult()->getResult(), sprintf('Not all errors are rendered'));
        }
    }
}
