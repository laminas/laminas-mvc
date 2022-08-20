<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Controller\Plugin;

use Laminas\Mvc\Controller\Plugin\Layout as LayoutPlugin;
use Laminas\Mvc\Exception\DomainException;
use Laminas\Mvc\MvcEvent;
use Laminas\View\Model\ViewModel;
use LaminasTest\Mvc\Controller\TestAsset\SampleController;
use PHPUnit\Framework\TestCase;

class LayoutTest extends TestCase
{
    public function setUp(): void
    {
        $this->event      = $event = new MvcEvent();
        $this->controller = new SampleController();
        $this->controller->setEvent($event);

        $this->plugin = $this->controller->plugin('layout');
    }

    public function testPluginWithoutControllerRaisesDomainException(): void
    {
        $plugin = new LayoutPlugin();
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('requires a controller');
        $plugin->setTemplate('home');
    }

    public function testSetTemplateAltersTemplateInEventViewModel(): void
    {
        $model = new ViewModel();
        $model->setTemplate('layout');
        $this->event->setViewModel($model);

        $this->plugin->setTemplate('alternate/layout');
        $this->assertEquals('alternate/layout', $model->getTemplate());
    }

    public function testInvokeProxiesToSetTemplate(): void
    {
        $model = new ViewModel();
        $model->setTemplate('layout');
        $this->event->setViewModel($model);

        $plugin = $this->plugin;
        $plugin('alternate/layout');
        $this->assertEquals('alternate/layout', $model->getTemplate());
    }

    public function testCallingInvokeWithNoArgumentsReturnsViewModel(): void
    {
        $model = new ViewModel();
        $model->setTemplate('layout');
        $this->event->setViewModel($model);

        $plugin = $this->plugin;
        $result = $plugin();
        $this->assertSame($model, $result);
    }
}
