<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Application;

use Laminas\Mvc\Application;
use Laminas\Mvc\MvcEvent;
use Laminas\Stdlib\ResponseInterface;
use PHPUnit\Framework\TestCase;

class InabilityToRetrieveControllerShouldTriggerDispatchErrorTest extends TestCase
{
    use MissingControllerTrait;

    /**
     * @group error-handling
     */
    public function testInabilityToRetrieveControllerShouldTriggerDispatchError(): void
    {
        $application = $this->prepareApplication();

        $events   = $application->getEventManager();
        $events->attach(MvcEvent::EVENT_DISPATCH_ERROR, static function (MvcEvent $e): ResponseInterface {
            $error      = $e->getError();
            $controller = $e->getController();
            $response = $e->getResponse();
            $response->setContent("Code: " . $error . '; Controller: ' . $controller);
            return $response;
        });

        $application->run();

        $response = $application->getMvcEvent()->getResponse();
        self::assertInstanceOf(ResponseInterface::class, $response);
        $this->assertStringContainsString(Application::ERROR_CONTROLLER_NOT_FOUND, $response->getContent());
        $this->assertStringContainsString('bad', $response->getContent());
    }
}
