<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Application;

use Laminas\Mvc\MvcEvent;
use PHPUnit\Framework\TestCase;

class ControllerIsDispatchedTest extends TestCase
{
    use PathControllerTrait;

    public function testControllerIsDispatchedDuringRun(): void
    {
        $application = $this->prepareApplication();

        $application->run();

        $response = $application->getMvcEvent()->getResponse();
        $this->assertStringContainsString('PathController', $response->getContent());
        $this->assertStringContainsString(MvcEvent::EVENT_DISPATCH, $response->toString());
    }
}
