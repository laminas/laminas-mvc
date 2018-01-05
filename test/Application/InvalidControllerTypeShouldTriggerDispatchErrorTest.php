<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Mvc\Application;

use PHPUnit\Framework\TestCase;
use Zend\Mvc\Application;
use Zend\Mvc\MvcEvent;

class InvalidControllerTypeShouldTrigerDispatchErrorTest extends TestCase
{
    use InvalidControllerTypeTrait;

    /**
     * @group error-handling
     */
    public function testInvalidControllerTypeShouldTriggerDispatchError()
    {
        $application = $this->prepareApplication();

        $response = $application->getResponse();
        $events   = $application->getEventManager();
        $events->attach(MvcEvent::EVENT_DISPATCH_ERROR, function ($e) use ($response) {
            $error      = $e->getError();
            $controller = $e->getController();
            $class      = $e->getControllerClass();
            $response->setContent("Code: " . $error . '; Controller: ' . $controller . '; Class: ' . $class);
            return $response;
        });

        $application->run();
        $this->assertContains(Application::ERROR_CONTROLLER_INVALID, $response->getContent());
        $this->assertContains('bad', $response->getContent());
    }
}
