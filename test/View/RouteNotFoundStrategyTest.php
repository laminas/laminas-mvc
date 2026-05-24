<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\View;

use Exception;
use Laminas\EventManager\EventManager;
use Laminas\EventManager\Test\EventListenerIntrospectionTrait;
use Laminas\Http\Response;
use Laminas\Mvc\Application;
use Laminas\Mvc\MvcEvent;
use Laminas\Mvc\View\Http\RouteNotFoundStrategy;
use Laminas\View\Model\ModelInterface;
use Laminas\View\Model\ViewModel;
use PHPUnit\Framework\TestCase;

class RouteNotFoundStrategyTest extends TestCase
{
    use EventListenerIntrospectionTrait;

    private RouteNotFoundStrategy $strategy;

    public function setUp(): void
    {
        $this->strategy = new RouteNotFoundStrategy();
    }

    public static function notFoundResponseProvider(): array
    {
        return [
            ['bar', 'assertEquals'],
            [null,  'assertTrue'],
            [new ViewModel(['message' => 'bar']), 'assertEquals'],
            [new ViewModel(),  'assertTrue'],
        ];
    }

    /**
     * @dataProvider notFoundResponseProvider
     */
    public function testLeavesReturnedMessageIntact(mixed $result, string $assertion): void
    {
        $response = new Response();
        $event    = new MvcEvent();
        $response->setStatusCode(404);
        $event->setResponse($response);

        $event->setResult($result);
        $this->strategy->prepareNotFoundViewModel($event);

        $viewModel = $event->getResult();
        self::assertInstanceOf(ModelInterface::class, $viewModel);

        $variables = $viewModel->getVariables();
        switch ($assertion) {
            case 'assertEquals':
                // Testing if we returned a message in the result
                self::assertEquals('bar', $variables['message']);
                break;
            case 'assertTrue':
                // Testing if no message was returned in the result; in that
                // case, default message is used from strategy
                self::assertTrue(isset($variables['message']));
                break;
        }
    }

    public function test404ErrorsInject404ResponseStatusCode(): void
    {
        $response = new Response();
        $event    = new MvcEvent();
        $errors   = [
            'error-controller-not-found' => Application::ERROR_CONTROLLER_NOT_FOUND,
            'error-controller-invalid'   => Application::ERROR_CONTROLLER_INVALID,
            'error-router-no-match'      => Application::ERROR_ROUTER_NO_MATCH,
        ];
        $event->setResponse($response);
        foreach ($errors as $key => $error) {
            $response->setStatusCode(200);
            $event->setError($error);
            $this->strategy->detectNotFoundError($event);
            self::assertTrue($response->isNotFound(), 'Failed asserting against ' . $key);
        }
    }

    public function testRouterAndDispatchErrorsInjectReasonInViewModelWhenAllowed(): void
    {
        $response = new Response();
        $event    = new MvcEvent();
        $errors   = [
            'error-controller-not-found' => Application::ERROR_CONTROLLER_NOT_FOUND,
            'error-controller-invalid'   => Application::ERROR_CONTROLLER_INVALID,
            'error-router-no-match'      => Application::ERROR_ROUTER_NO_MATCH,
        ];
        $event->setResponse($response);
        foreach ([true, false] as $allow) {
            $this->strategy->setDisplayNotFoundReason($allow);
            foreach ($errors as $key => $error) {
                $response->setStatusCode(200);
                $event->setResult(null);
                $event->setError($error);
                $this->strategy->detectNotFoundError($event);
                $this->strategy->prepareNotFoundViewModel($event);
                $viewModel = $event->getResult();
                self::assertInstanceOf(ModelInterface::class, $viewModel);
                $variables = $viewModel->getVariables();
                if ($allow) {
                    self::assertTrue(isset($variables['reason']));
                    self::assertEquals($key, $variables['reason']);
                } else {
                    self::assertFalse(isset($variables['reason']));
                }
            }
        }
    }

    public function testNon404ErrorsInjectNoStatusCode(): void
    {
        $response = new Response();
        $event    = new MvcEvent();
        $errors   = [
            Application::ERROR_EXCEPTION,
            'custom-error',
            null,
        ];
        foreach ($errors as $error) {
            $response->setStatusCode(200);
            $event->setError($error);
            $this->strategy->detectNotFoundError($event);
            self::assertFalse($response->isNotFound());
        }
    }

    public function testResponseAsResultDoesNotPrepare404ViewModel(): void
    {
        $response = new Response();
        $event    = new MvcEvent();
        $event->setResponse($response)
              ->setResult($response);

        $this->strategy->prepareNotFoundViewModel($event);
        $model = $event->getResult();
        if ($model instanceof ViewModel) {
            self::assertNotEquals($this->strategy->getNotFoundTemplate(), $model->getTemplate());
            $variables = $model->getVariables();
            self::assertArrayNotHasKey('message', $variables);
        }

        $this->addToAssertionCount(1);
    }

    public function testNon404ResponseDoesNotPrepare404ViewModel(): void
    {
        $response = new Response();
        $event    = new MvcEvent();
        $response->setStatusCode(200);
        $event->setResponse($response);

        $this->strategy->prepareNotFoundViewModel($event);
        $model = $event->getResult();
        if ($model instanceof ViewModel) {
            self::assertNotEquals($this->strategy->getNotFoundTemplate(), $model->getTemplate());
            $variables = $model->getVariables();
            self::assertArrayNotHasKey('message', $variables);
        }

        $this->addToAssertionCount(1);
    }

    public function test404ResponsePrepares404ViewModelWithTemplateFromStrategy(): void
    {
        $response = new Response();
        $event    = new MvcEvent();
        $response->setStatusCode(404);
        $event->setResponse($response);

        $this->strategy->prepareNotFoundViewModel($event);
        $model = $event->getResult();
        self::assertInstanceOf(ModelInterface::class, $model);
        self::assertEquals($this->strategy->getNotFoundTemplate(), $model->getTemplate());
        $variables = $model->getVariables();
        self::assertTrue(isset($variables['message']));
    }

    public function test404ResponsePrepares404ViewModelWithReasonWhenAllowed(): void
    {
        $response = new Response();
        $event    = new MvcEvent();

        foreach ([true, false] as $allow) {
            $this->strategy->setDisplayNotFoundReason($allow);
            $response->setStatusCode(404);
            $event->setResult(null);
            $event->setResponse($response);
            $this->strategy->prepareNotFoundViewModel($event);
            $model = $event->getResult();
            self::assertInstanceOf(ModelInterface::class, $model);
            $variables = $model->getVariables();
            if ($allow) {
                self::assertTrue(isset($variables['reason']));
                self::assertEquals(Application::ERROR_CONTROLLER_CANNOT_DISPATCH, $variables['reason']);
            } else {
                self::assertFalse(isset($variables['reason']));
            }
        }
    }

    public function test404ResponsePrepares404ViewModelWithExceptionWhenAllowed(): void
    {
        $response  = new Response();
        $event     = new MvcEvent();
        $exception = new Exception();
        $event->setParam('exception', $exception);

        foreach ([true, false] as $allow) {
            $this->strategy->setDisplayExceptions($allow);
            $response->setStatusCode(404);
            $event->setResult(null);
            $event->setResponse($response);
            $this->strategy->prepareNotFoundViewModel($event);
            $model = $event->getResult();
            self::assertInstanceOf(ModelInterface::class, $model);
            $variables = $model->getVariables();
            if ($allow) {
                self::assertTrue($variables['display_exceptions']);
                self::assertTrue(isset($variables['exception']));
                self::assertSame($exception, $variables['exception']);
            } else {
                self::assertFalse(isset($variables['exception']));
            }
        }
    }

    public function test404ResponsePrepares404ViewModelWithControllerWhenAllowed(): void
    {
        $response        = new Response();
        $event           = new MvcEvent();
        $controller      = 'some-or-other';
        $controllerClass = 'Some\Controller\OrOtherController';
        $event->setController($controller);
        $event->setControllerClass($controllerClass);

        foreach (['setDisplayNotFoundReason', 'setDisplayExceptions'] as $method) {
            foreach ([true, false] as $allow) {
                $this->strategy->$method($allow);
                $response->setStatusCode(404);
                $event->setResult(null);
                $event->setResponse($response);
                $this->strategy->prepareNotFoundViewModel($event);
                $model = $event->getResult();
                self::assertInstanceOf(ModelInterface::class, $model);
                $variables = $model->getVariables();
                if ($allow) {
                    self::assertTrue(isset($variables['controller']));
                    self::assertEquals($controller, $variables['controller']);
                    self::assertTrue(isset($variables['controller_class']));
                    self::assertEquals($controllerClass, $variables['controller_class']);
                } else {
                    self::assertFalse(isset($variables['controller']));
                    self::assertFalse(isset($variables['controller_class']));
                }
            }
        }
    }

    public function testInjectsHttpResponseIntoEventIfNoneAlreadyPresent(): void
    {
        $event  = new MvcEvent();
        $errors = [
            'not-found' => Application::ERROR_CONTROLLER_NOT_FOUND,
            'invalid'   => Application::ERROR_CONTROLLER_INVALID,
        ];
        foreach ($errors as $key => $error) {
            $event->setError($error);
            $this->strategy->detectNotFoundError($event);
            $response = $event->getResponse();
            self::assertInstanceOf(Response::class, $response);
            self::assertTrue($response->isNotFound(), 'Failed asserting against ' . $key);
        }
    }

    public function testNotFoundTemplateDefaultsToError(): void
    {
        self::assertEquals('error', $this->strategy->getNotFoundTemplate());
    }

    public function testNotFoundTemplateIsMutable()
    {
        $this->strategy->setNotFoundTemplate('alternate/error');
        self::assertEquals('alternate/error', $this->strategy->getNotFoundTemplate());
    }

    public function testAttachesListenersAtExpectedPriorities(): void
    {
        $events = new EventManager();
        $this->strategy->attach($events);

        $evs = [
            MvcEvent::EVENT_DISPATCH       => -90,
            MvcEvent::EVENT_DISPATCH_ERROR => 1,
        ];
        foreach ($evs as $event => $expectedPriority) {
            self::assertListenerAtPriority(
                [$this->strategy, 'prepareNotFoundViewModel'],
                $expectedPriority,
                $event,
                $events
            );
        }

        self::assertListenerAtPriority(
            [$this->strategy, 'detectNotFoundError'],
            1,
            $event,
            $events
        );
    }

    public function testDetachesListeners(): void
    {
        $events = new EventManager();
        $this->strategy->attach($events);
        $listeners = $this->getArrayOfListenersForEvent(MvcEvent::EVENT_DISPATCH, $events);
        self::assertCount(1, $listeners);
        $listeners = $this->getArrayOfListenersForEvent(MvcEvent::EVENT_DISPATCH_ERROR, $events);
        self::assertCount(2, $listeners);

        $this->strategy->detach($events);

        $listeners = $this->getArrayOfListenersForEvent(MvcEvent::EVENT_DISPATCH, $events);
        self::assertCount(0, $listeners);
        $listeners = $this->getArrayOfListenersForEvent(MvcEvent::EVENT_DISPATCH_ERROR, $events);
        self::assertCount(0, $listeners);
    }
}
