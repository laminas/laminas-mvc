<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
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
