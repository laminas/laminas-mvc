<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc\Controller;

use PHPUnit\Framework\TestCase;
use Zend\EventManager\EventManager;
use Zend\EventManager\SharedEventManager;
use Zend\Mvc\Controller\ControllerManager;
use Zend\Mvc\Controller\PluginManager;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceManager;

class IntegrationTest extends TestCase
{
    public function setUp()
    {
        $this->sharedEvents = new SharedEventManager();

        $this->services = new ServiceManager();
        (new Config([
            'services' => [
                'SharedEventManager' => $this->sharedEvents,
            ],
            'factories' => [
                'ControllerPluginManager' => function ($services) {
                    return new PluginManager($services);
                },
                'EventManager' => function () {
                    return new EventManager($this->sharedEvents);
                },
            ],
            'shared' => [
                'EventManager' => false,
            ],
        ]))->configureServiceManager($this->services);
    }

    public function testPluginReceivesCurrentController()
    {
        $controllers = new ControllerManager($this->services, ['factories' => [
            'first'  => function ($services) {
                return new TestAsset\SampleController();
            },
            'second' => function ($services) {
                return new TestAsset\SampleController();
            },
        ]]);

        $first  = $controllers->get('first');
        $second = $controllers->get('second');
        $this->assertNotSame($first, $second);

        $plugin1 = $first->plugin('url');
        $this->assertSame($first, $plugin1->getController());

        $plugin2 = $second->plugin('url');
        $this->assertSame($second, $plugin2->getController());

        $this->assertSame($plugin1, $plugin2);
    }
}
