<?php

namespace LaminasTest\Mvc\Application;

use Laminas\Http\PhpEnvironment\Response;
use Laminas\Mvc\MvcEvent;
use PHPUnit\Framework\TestCase;

class AllowsReturningEarlyFromRoutingTest extends TestCase
{
    use PathControllerTrait;

    public function testAllowsReturningEarlyFromRouting()
    {
        $application = $this->prepareApplication();

        $response = new Response();

        $application->getEventManager()->attach(MvcEvent::EVENT_ROUTE, function ($e) use ($response) {
            return $response;
        });

        $result = $application->run();
        $this->assertSame($application, $result);
        $this->assertSame($response, $result->getResponse());
    }
}
