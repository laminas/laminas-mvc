<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\View;

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

    /**
     * @var RouteNotFoundStrategy
     */
    private $strategy;

    public function setUp()
    {
        $this->strategy = new RouteNotFoundStrategy();
    }

    public function notFoundResponseProvider()
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
    public function testLeavesReturnedMessageIntact($result, $assertion)
    {
        $response = new Response();
        $event    = new MvcEvent();
        $response->setStatusCode(404);
        $event->setResponse($response);

        $event->setResult($result);
        $this->strategy->prepareNotFoundViewModel($event);

        $viewModel = $event->getResult();
        $this->assertInstanceOf(ModelInterface::class, $viewModel);

        $variables = $viewModel->getVariables();
        switch ($assertion) {
            case 'assertEquals':
                // Testing if we returned a message in the result
                $this->assertEquals('bar', $variables['message']);
                break;
            case 'assertTrue':
                // Testing if no message was returned in the result; in that
                // case, default message is used from strategy
                $this->assertTrue(isset($variables['message']));
                break;
        }
    }

    public function test404ErrorsInject404ResponseStatusCode()
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
            $this->assertTrue($response->isNotFound(), 'Failed asserting against ' . $key);
        }
    }

    public function testRouterAndDispatchErrorsInjectReasonInViewModelWhenAllowed()
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
                $this->assertInstanceOf(ModelInterface::class, $viewModel);
                $variables = $viewModel->getVariables();
                if ($allow) {
                    $this->assertTrue(isset($variables['reason']));
                    $this->assertEquals($key, $variables['reason']);
                } else {
                    $this->assertFalse(isset($variables['reason']));
                }
            }
        }
    }

    public function testNon404ErrorsInjectNoStatusCode()
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
            $this->assertFalse($response->isNotFound());
        }
    }

    public function testResponseAsResultDoesNotPrepare404ViewModel()
    {
        $response = new Response();
        $event    = new MvcEvent();
        $event->setResponse($response)
              ->setResult($response);

        $this->strategy->prepareNotFoundViewModel($event);
        $model = $event->getResult();
        if ($model instanceof ViewModel) {
            $this->assertNotEquals($this->strategy->getNotFoundTemplate(), $model->getTemplate());
            $variables = $model->getVariables();
            $this->assertArrayNotHasKey('message', $variables);
        }

        $this->addToAssertionCount(1);
    }

    public function testNon404ResponseDoesNotPrepare404ViewModel()
    {
        $response = new Response();
        $event    = new MvcEvent();
        $response->setStatusCode(200);
        $event->setResponse($response);

        $this->strategy->prepareNotFoundViewModel($event);
        $model = $event->getResult();
        if ($model instanceof ViewModel) {
            $this->assertNotEquals($this->strategy->getNotFoundTemplate(), $model->getTemplate());
            $variables = $model->getVariables();
            $this->assertArrayNotHasKey('message', $variables);
        }

        $this->addToAssertionCount(1);
    }

    public function test404ResponsePrepares404ViewModelWithTemplateFromStrategy()
    {
        $response = new Response();
        $event    = new MvcEvent();
        $response->setStatusCode(404);
        $event->setResponse($response);

        $this->strategy->prepareNotFoundViewModel($event);
        $model = $event->getResult();
        $this->assertInstanceOf(ModelInterface::class, $model);
        $this->assertEquals($this->strategy->getNotFoundTemplate(), $model->getTemplate());
        $variables = $model->getVariables();
        $this->assertTrue(isset($variables['message']));
    }

    public function test404ResponsePrepares404ViewModelWithReasonWhenAllowed()
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
            $this->assertInstanceOf(ModelInterface::class, $model);
            $variables = $model->getVariables();
            if ($allow) {
                $this->assertTrue(isset($variables['reason']));
                $this->assertEquals(Application::ERROR_CONTROLLER_CANNOT_DISPATCH, $variables['reason']);
            } else {
                $this->assertFalse(isset($variables['reason']));
            }
        }
    }

    public function test404ResponsePrepares404ViewModelWithExceptionWhenAllowed()
    {
        $response  = new Response();
        $event     = new MvcEvent();
        $exception = new \Exception();
        $event->setParam('exception', $exception);

        foreach ([true, false] as $allow) {
            $this->strategy->setDisplayExceptions($allow);
            $response->setStatusCode(404);
            $event->setResult(null);
            $event->setResponse($response);
            $this->strategy->prepareNotFoundViewModel($event);
            $model = $event->getResult();
            $this->assertInstanceOf(ModelInterface::class, $model);
            $variables = $model->getVariables();
            if ($allow) {
                $this->assertTrue($variables['display_exceptions']);
                $this->assertTrue(isset($variables['exception']));
                $this->assertSame($exception, $variables['exception']);
            } else {
                $this->assertFalse(isset($variables['exception']));
            }
        }
    }

    public function test404ResponsePrepares404ViewModelWithControllerWhenAllowed()
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
                $this->assertInstanceOf(ModelInterface::class, $model);
                $variables = $model->getVariables();
                if ($allow) {
                    $this->assertTrue(isset($variables['controller']));
                    $this->assertEquals($controller, $variables['controller']);
                    $this->assertTrue(isset($variables['controller_class']));
                    $this->assertEquals($controllerClass, $variables['controller_class']);
                } else {
                    $this->assertFalse(isset($variables['controller']));
                    $this->assertFalse(isset($variables['controller_class']));
                }
            }
        }
    }

    public function testInjectsHttpResponseIntoEventIfNoneAlreadyPresent()
    {
        $event    = new MvcEvent();
        $errors   = [
            'not-found' => Application::ERROR_CONTROLLER_NOT_FOUND,
            'invalid'   => Application::ERROR_CONTROLLER_INVALID,
        ];
        foreach ($errors as $key => $error) {
            $event->setError($error);
            $this->strategy->detectNotFoundError($event);
            $response = $event->getResponse();
            $this->assertInstanceOf(Response::class, $response);
            $this->assertTrue($response->isNotFound(), 'Failed asserting against ' . $key);
        }
    }

    public function testNotFoundTemplateDefaultsToError()
    {
        $this->assertEquals('error', $this->strategy->getNotFoundTemplate());
    }

    public function testNotFoundTemplateIsMutable()
    {
        $this->strategy->setNotFoundTemplate('alternate/error');
        $this->assertEquals('alternate/error', $this->strategy->getNotFoundTemplate());
    }

    public function testAttachesListenersAtExpectedPriorities()
    {
        $events = new EventManager();
        $this->strategy->attach($events);

        $evs = [
            MvcEvent::EVENT_DISPATCH => -90,
            MvcEvent::EVENT_DISPATCH_ERROR => 1
        ];
        foreach ($evs as $event => $expectedPriority) {
            $this->assertListenerAtPriority(
                [$this->strategy, 'prepareNotFoundViewModel'],
                $expectedPriority,
                $event,
                $events
            );
        }

        $this->assertListenerAtPriority(
            [$this->strategy, 'detectNotFoundError'],
            1,
            $event,
            $events
        );
    }

    public function testDetachesListeners()
    {
        $events = new EventManager();
        $this->strategy->attach($events);
        $listeners = $this->getArrayOfListenersForEvent(MvcEvent::EVENT_DISPATCH, $events);
        $this->assertCount(1, $listeners);
        $listeners = $this->getArrayOfListenersForEvent(MvcEvent::EVENT_DISPATCH_ERROR, $events);
        $this->assertCount(2, $listeners);

        $this->strategy->detach($events);

        $listeners = $this->getArrayOfListenersForEvent(MvcEvent::EVENT_DISPATCH, $events);
        $this->assertCount(0, $listeners);
        $listeners = $this->getArrayOfListenersForEvent(MvcEvent::EVENT_DISPATCH_ERROR, $events);
        $this->assertCount(0, $listeners);
    }
}
