<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\View;

use Exception;
use Laminas\EventManager\EventManager;
use Laminas\EventManager\Test\EventListenerIntrospectionTrait;
use Laminas\Http\Response;
use Laminas\Mvc\Application;
use Laminas\Mvc\MvcEvent;
use Laminas\Mvc\View\Http\ExceptionStrategy;
use Laminas\View\Model\ViewModel;
use PHPUnit\Framework\TestCase;

use function count;

class ExceptionStrategyTest extends TestCase
{
    use EventListenerIntrospectionTrait;

    private ExceptionStrategy $strategy;

    public function setUp(): void
    {
        $this->strategy = new ExceptionStrategy();
    }

    public function testDisplayExceptionsIsDisabledByDefault(): void
    {
        self::assertFalse($this->strategy->displayExceptions());
    }

    public function testDisplayExceptionsFlagIsMutable(): void
    {
        $this->strategy->setDisplayExceptions(true);
        self::assertTrue($this->strategy->displayExceptions());
    }

    public function testExceptionTemplateHasASaneDefault(): void
    {
        self::assertEquals('error', $this->strategy->getExceptionTemplate());
    }

    public function testExceptionTemplateIsMutable(): void
    {
        $this->strategy->setExceptionTemplate('pages/error');
        self::assertEquals('pages/error', $this->strategy->getExceptionTemplate());
    }

    public function test404ApplicationErrorsResultInNoOperations(): void
    {
        $event = new MvcEvent();
        foreach ([Application::ERROR_CONTROLLER_NOT_FOUND, Application::ERROR_CONTROLLER_INVALID] as $error) {
            $event->setError($error);
            $this->strategy->prepareExceptionViewModel($event);
            $response = $event->getResponse();
            if (null !== $response) {
                self::assertNotEquals(500, $response->getStatusCode());
            }
            $model = $event->getResult();
            if (null !== $model) {
                $variables = $model->getVariables();
                self::assertArrayNotHasKey('message', $variables);
                self::assertArrayNotHasKey('exception', $variables);
                self::assertArrayNotHasKey('display_exceptions', $variables);
                self::assertNotEquals('error', $model->getTemplate());
            }
        }

        $this->addToAssertionCount(1);
    }

    public function testCatchesApplicationExceptions(): void
    {
        $exception = new Exception();
        $event     = new MvcEvent();
        $event->setParam('exception', $exception);
        $event->setError(Application::ERROR_EXCEPTION);
        $this->strategy->prepareExceptionViewModel($event);

        $response = $event->getResponse();
        self::assertTrue($response->isServerError());

        $model = $event->getResult();
        self::assertInstanceOf(ViewModel::class, $model);
        self::assertEquals($this->strategy->getExceptionTemplate(), $model->getTemplate());

        $variables = $model->getVariables();
        self::assertArrayHasKey('message', $variables);
        self::assertStringContainsString('error occurred', $variables['message']);
        self::assertArrayHasKey('exception', $variables);
        self::assertSame($exception, $variables['exception']);
        self::assertArrayHasKey('display_exceptions', $variables);
        self::assertEquals($this->strategy->displayExceptions(), $variables['display_exceptions']);
    }

    public function testCatchesUnknownErrorTypes(): void
    {
        $exception = new Exception();
        $event     = new MvcEvent();
        $event->setParam('exception', $exception);
        $event->setError('custom_error');
        $this->strategy->prepareExceptionViewModel($event);

        $response = $event->getResponse();
        self::assertTrue($response->isServerError());
    }

    public function testEmptyErrorInEventResultsInNoOperations(): void
    {
        $event = new MvcEvent();
        $this->strategy->prepareExceptionViewModel($event);
        $response = $event->getResponse();
        if (null !== $response) {
            self::assertNotEquals(500, $response->getStatusCode());
        }
        $model = $event->getResult();
        if (null !== $model) {
            $variables = $model->getVariables();
            self::assertArrayNotHasKey('message', $variables);
            self::assertArrayNotHasKey('exception', $variables);
            self::assertArrayNotHasKey('display_exceptions', $variables);
            self::assertNotEquals('error', $model->getTemplate());
        }

        $this->addToAssertionCount(1);
    }

    public function testDoesNothingIfEventResultIsAResponse(): void
    {
        $event    = new MvcEvent();
        $response = new Response();
        $event->setResponse($response);
        $event->setResult($response);
        $event->setError('foobar');

        self::assertNull($this->strategy->prepareExceptionViewModel($event));
    }

    public function testAttachesListenerAtExpectedPriority(): void
    {
        $events = new EventManager();
        $this->strategy->attach($events);

        self::assertListenerAtPriority(
            [$this->strategy, 'prepareExceptionViewModel'],
            1,
            MvcEvent::EVENT_DISPATCH_ERROR,
            $events
        );
    }

    public function testDetachesListeners(): void
    {
        $events = new EventManager();
        $this->strategy->attach($events);
        $listeners = $this->getArrayOfListenersForEvent(MvcEvent::EVENT_DISPATCH_ERROR, $events);
        self::assertEquals(1, count($listeners));
        $this->strategy->detach($events);
        $listeners = $this->getArrayOfListenersForEvent(MvcEvent::EVENT_DISPATCH_ERROR, $events);
        self::assertEquals(0, count($listeners));
    }

    public function testReuseResponseStatusCodeIfItExists(): void
    {
        $event    = new MvcEvent();
        $response = new Response();
        $response->setStatusCode(401);
        $event->setResponse($response);
        $this->strategy->prepareExceptionViewModel($event);
        $response = $event->getResponse();
        if (null !== $response) {
            self::assertEquals(401, $response->getStatusCode());
        }
        $model = $event->getResult();
        if (null !== $model) {
            $variables = $model->getVariables();
            self::assertArrayNotHasKey('message', $variables);
            self::assertArrayNotHasKey('exception', $variables);
            self::assertArrayNotHasKey('display_exceptions', $variables);
            self::assertNotEquals('error', $model->getTemplate());
        }
    }
}
