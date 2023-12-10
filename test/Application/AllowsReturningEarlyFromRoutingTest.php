<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Application;

use Laminas\Http\PhpEnvironment\Response;
use Laminas\Mvc\MvcEvent;
use PHPUnit\Framework\TestCase;

class AllowsReturningEarlyFromRoutingTest extends TestCase
{
    use PathControllerTrait;

    public function testAllowsReturningEarlyFromRouting(): void
    {
        $application = $this->prepareApplication();

        $response = new Response();

        $application->getEventManager()->attach(MvcEvent::EVENT_ROUTE, static fn($e): Response => $response);

        $application->run();

        $this->assertSame($response, $application->getMvcEvent()->getResponse());
    }
}
