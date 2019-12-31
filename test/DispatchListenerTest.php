<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc;

use Laminas\Mvc\Application;
use Laminas\Mvc\MvcEvent;
use Laminas\Mvc\Router;
use Laminas\Mvc\Service\ServiceListenerFactory;
use Laminas\Mvc\Service\ServiceManagerConfig;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Stdlib\ArrayUtils;
use PHPUnit_Framework_TestCase as TestCase;

class DispatchListenerTest extends TestCase
{
    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * @var Application
     */
    protected $application;

    public function setUp()
    {
        $serviceConfig = ArrayUtils::merge(
            $this->readAttribute(new ServiceListenerFactory, 'defaultServiceConfig'),
            array(
                'allow_override' => true,
                'invokables' => array(
                    'Request'              => 'Laminas\Http\PhpEnvironment\Request',
                    'Response'             => 'Laminas\Http\PhpEnvironment\Response',
                    'ViewManager'          => 'LaminasTest\Mvc\TestAsset\MockViewManager',
                    'SendResponseListener' => 'LaminasTest\Mvc\TestAsset\MockSendResponseListener',
                    'BootstrapListener'    => 'LaminasTest\Mvc\TestAsset\StubBootstrapListener',
                ),
                'aliases' => array(
                    'Router'                 => 'HttpRouter',
                ),
                'services' => array(
                    'Config' => array(),
                    'ApplicationConfig' => array(
                        'modules' => array(),
                        'module_listener_options' => array(
                            'config_cache_enabled' => false,
                            'cache_dir'            => 'data/cache',
                            'module_paths'         => array(),
                        ),
                    ),
                ),
            )
        );
        $this->serviceManager = new ServiceManager(new ServiceManagerConfig($serviceConfig));
        $this->application = $this->serviceManager->get('Application');
    }

    public function setupPathController()
    {
        $request = $this->serviceManager->get('Request');
        $request->setUri('http://example.local/path');

        $router = $this->serviceManager->get('HttpRouter');
        $route  = Router\Http\Literal::factory(array(
            'route'    => '/path',
            'defaults' => array(
                'controller' => 'path',
            ),
        ));
        $router->addRoute('path', $route);
        $this->application->bootstrap();
    }

    public function testControllerLoaderComposedOfAbstractFactory()
    {
        $this->setupPathController();

        $controllerLoader = $this->serviceManager->get('ControllerLoader');
        $controllerLoader->addAbstractFactory('LaminasTest\Mvc\Controller\TestAsset\ControllerLoaderAbstractFactory');

        $log = array();
        $this->application->getEventManager()->attach(MvcEvent::EVENT_DISPATCH_ERROR, function ($e) use (&$log) {
            $log['error'] = $e->getError();
        });

        $this->application->run();

        $event = $this->application->getMvcEvent();
        $dispatchListener = $this->serviceManager->get('DispatchListener');
        $return = $dispatchListener->onDispatch($event);

        $this->assertEmpty($log);
        $this->assertInstanceOf('Laminas\Http\PhpEnvironment\Response', $return);
        $this->assertSame(200, $return->getStatusCode());
    }

    public function testUnlocatableControllerLoaderComposedOfAbstractFactory()
    {
        $this->setupPathController();

        $controllerLoader = $this->serviceManager->get('ControllerLoader');
        $controllerLoader->addAbstractFactory('LaminasTest\Mvc\Controller\TestAsset\UnlocatableControllerLoaderAbstractFactory');

        $log = array();
        $this->application->getEventManager()->attach(MvcEvent::EVENT_DISPATCH_ERROR, function ($e) use (&$log) {
            $log['error'] = $e->getError();
        });

        $this->application->run();
        $event = $this->application->getMvcEvent();
        $dispatchListener = $this->serviceManager->get('DispatchListener');
        $return = $dispatchListener->onDispatch($event);

        $this->assertArrayHasKey('error', $log);
        $this->assertSame('error-controller-not-found', $log['error']);
    }
}
