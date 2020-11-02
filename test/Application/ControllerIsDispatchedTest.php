<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Application;

use Laminas\Mvc\MvcEvent;
use PHPUnit\Framework\TestCase;

class ControllerIsDispatchedTest extends TestCase
{
    use PathControllerTrait;

    public function testControllerIsDispatchedDuringRun()
    {
        $application = $this->prepareApplication();

        $response = $application->run()->getResponse();
        $this->assertStringContainsString('PathController', $response->getContent());
        $this->assertStringContainsString(MvcEvent::EVENT_DISPATCH, $response->toString());
    }
}
