<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Application;

use Laminas\Mvc\MvcEvent;
use PHPUnit\Framework\TestCase;

class ExceptionsRaisedInDispatchableShouldRaiseDispatchErrorEventTest extends TestCase
{
    use BadControllerTrait;

    /**
     * @group error-handling
     */
    public function testExceptionsRaisedInDispatchableShouldRaiseDispatchErrorEvent()
    {
        $application = $this->prepareApplication();

        $response = $application->getResponse();
        $events   = $application->getEventManager();
        $events->attach(MvcEvent::EVENT_DISPATCH_ERROR, function ($e) use ($response) {
            $exception = $e->getParam('exception');
            $this->assertInstanceOf('Exception', $exception);
            $response->setContent($exception->getMessage());
            return $response;
        });

        $application->run();
        $this->assertStringContainsString('Raised an exception', $response->getContent());
    }
}
