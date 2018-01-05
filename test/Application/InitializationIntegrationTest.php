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

class InitializationIntegrationTest extends TestCase
{
    public function testDefaultInitializationWorkflow()
    {
        $appConfig = [
            'modules' => [
                'Zend\Router',
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
