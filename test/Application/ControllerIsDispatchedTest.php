<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Mvc\Application;

use PHPUnit\Framework\TestCase;
use Zend\Mvc\MvcEvent;

class ControllerIsDispatchedTest extends TestCase
{
    use PathControllerTrait;

    public function testControllerIsDispatchedDuringRun()
    {
        $application = $this->prepareApplication();

        $response = $application->run()->getResponse();
        $this->assertContains('PathController', $response->getContent());
        $this->assertContains(MvcEvent::EVENT_DISPATCH, $response->toString());
    }
}
