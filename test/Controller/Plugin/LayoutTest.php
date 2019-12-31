<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Controller\Plugin;

use Laminas\Mvc\Controller\Plugin\Layout as LayoutPlugin;
use Laminas\Mvc\MvcEvent;
use Laminas\View\Model\ViewModel;
use LaminasTest\Mvc\Controller\TestAsset\SampleController;
use PHPUnit_Framework_TestCase as TestCase;

class LayoutTest extends TestCase
{
    public function setUp()
    {
        $this->event      = $event = new MvcEvent();
        $this->controller = new SampleController();
        $this->controller->setEvent($event);

        $this->plugin = $this->controller->plugin('layout');
    }

    public function testPluginWithoutControllerRaisesDomainException()
    {
        $plugin = new LayoutPlugin();
        $this->setExpectedException('Laminas\Mvc\Exception\DomainException', 'requires a controller');
        $plugin->setTemplate('home');
    }

    public function testSetTemplateAltersTemplateInEventViewModel()
    {
        $model = new ViewModel();
        $model->setTemplate('layout');
        $this->event->setViewModel($model);

        $this->plugin->setTemplate('alternate/layout');
        $this->assertEquals('alternate/layout', $model->getTemplate());
    }

    public function testInvokeProxiesToSetTemplate()
    {
        $model = new ViewModel();
        $model->setTemplate('layout');
        $this->event->setViewModel($model);

        $plugin = $this->plugin;
        $plugin('alternate/layout');
        $this->assertEquals('alternate/layout', $model->getTemplate());
    }

    public function testCallingInvokeWithNoArgumentsReturnsViewModel()
    {
        $model = new ViewModel();
        $model->setTemplate('layout');
        $this->event->setViewModel($model);

        $plugin = $this->plugin;
        $result = $plugin();
        $this->assertSame($model, $result);
    }
}
