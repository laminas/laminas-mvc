<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Application;

use Laminas\Mvc\Application;
use Laminas\Mvc\MvcEvent;
use LaminasTest\Mvc\TestAsset;
use PHPUnit_Framework_TestCase as TestCase;

class InitializationIntegrationTest extends TestCase
{
    public function testDefaultInitializationWorkflow()
    {
        $appConfig = [
            'modules' => [
                'Laminas\Router',
                'Application',
            ],
            'module_listener_options' => [
                'module_paths' => [
                    __DIR__ . '/TestAsset/modules',
                ],
            ],
        ];

        $application = Application::init($appConfig);

        $request = $application->getRequest();
        $request->setUri('http://example.local/path');
        $request->setRequestUri('/path');

        ob_start();
        $application->run();
        $content = ob_get_clean();

        $response = $application->getResponse();
        $this->assertContains('Application\\Controller\\PathController', $response->getContent());
        $this->assertContains('Application\\Controller\\PathController', $content);
        $this->assertContains(MvcEvent::EVENT_DISPATCH, $response->toString());
    }
}
