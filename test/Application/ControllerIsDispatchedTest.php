<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Application;

use Laminas\Mvc\MvcEvent;
use PHPUnit_Framework_Error_Deprecated;
use PHPUnit_Framework_TestCase as TestCase;

class ControllerIsDispatchedTest extends TestCase
{
    use PathControllerTrait;

    public function setUp()
    {
        // Ignore deprecation errors
        PHPUnit_Framework_Error_Deprecated::$enabled = false;
    }

    public function testControllerIsDispatchedDuringRun()
    {
        $application = $this->prepareApplication();

        $response = $application->run()->getResponse();
        $this->assertContains('PathController', $response->getContent());
        $this->assertContains(MvcEvent::EVENT_DISPATCH, $response->toString());
    }
}
