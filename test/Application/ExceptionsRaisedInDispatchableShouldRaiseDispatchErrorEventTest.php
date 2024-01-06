<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Application;

use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;
use Laminas\Stdlib\ResponseInterface;
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

        $events = $application->getEventManager();
        $events->attach(MvcEvent::EVENT_DISPATCH_ERROR, function (MvcEvent $e): ResponseInterface {
            $exception = $e->getParam('exception');
            $this->assertInstanceOf('Exception', $exception);
            $response = $e->getResponse();
            $response->setContent($exception->getMessage());
            return $response;
        });

        $application->run();

        $response = $application->getMvcEvent()->getResponse();
        self::assertInstanceOf(Response::class, $response);
        $this->assertStringContainsString('Raised an exception', $response->getContent());
    }
}
