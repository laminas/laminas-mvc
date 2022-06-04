<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Application;

use Laminas\Mvc\MvcEvent;
use PHPUnit\Framework\TestCase;

class ExceptionsRaisedInDispatchableShouldRaiseDispatchErrorEventTest extends TestCase
{
    use BadControllerTrait;

    /**
     * @group error-handling
     */
    public function testExceptionsRaisedInDispatchableShouldRaiseDispatchErrorEvent(): void
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
