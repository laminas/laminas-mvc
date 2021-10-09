<?php

namespace LaminasTest\Mvc\Controller\Plugin;

use Laminas\Http\Header\Accept;
use Laminas\Http\Header\Accept\FieldValuePart\AcceptFieldValuePart;
use Laminas\Http\Request;
use Laminas\Mvc\Exception\InvalidArgumentException;
use Laminas\Mvc\MvcEvent;
use Laminas\View\Model;
use LaminasTest\Mvc\Controller\TestAsset\SampleController;
use PHPUnit\Framework\TestCase;

class AcceptableViewModelSelectorTest extends TestCase
{
    public function setUp(): void
    {
        $this->request = new Request();

        $event = new MvcEvent();
        $event->setRequest($this->request);
        $this->event = $event;

        $this->controller = new SampleController();
        $this->controller->setEvent($event);

        $this->plugin = $this->controller->plugin('acceptableViewModelSelector');
    }

    public function testHonorsAcceptPrecedenceAndPriorityWhenInvoked()
    {
        $arr = [
            Model\JsonModel::class => [
                'application/json',
                'application/javascript'
            ],
            Model\FeedModel::class => [
                'application/rss+xml',
                'application/atom+xml'
            ],
            Model\ViewModel::class => '*/*'
        ];

        $header   = Accept::fromString(
            'Accept: text/plain; q=0.5, text/html, text/xml; q=0, text/x-dvi; q=0.8, text/x-c'
        );
        $this->request->getHeaders()->addHeader($header);
        $plugin   = $this->plugin;
        $plugin->setDefaultViewModelName(Model\FeedModel::class);
        $result   = $plugin($arr);

        $this->assertInstanceOf(Model\ViewModel::class, $result);
        $this->assertNotInstanceOf(Model\FeedModel::class, $result); // Ensure the default wasn't selected
        $this->assertNotInstanceOf(Model\JsonModel::class, $result);
    }

    public function testDefaultViewModelName()
    {
        $arr = [
            Model\JsonModel::class => [
                'application/json',
                'application/javascript'
            ],
            Model\FeedModel::class => [
                'application/rss+xml',
                'application/atom+xml'
            ],
        ];

        $header   = Accept::fromString('Accept: text/plain');
        $this->request->getHeaders()->addHeader($header);
        $plugin   = $this->plugin;
        $result   = $plugin->getViewModelName($arr);

        $this->assertEquals(Model\ViewModel::class, $result); //   Default Default View Model Name

        $plugin->setDefaultViewModelName(Model\FeedModel::class);
        $this->assertEquals($plugin->getDefaultViewModelName(), Model\FeedModel::class); // Test getter along the way
        $this->assertInstanceOf(Model\FeedModel::class, $plugin($arr));
    }

    public function testSelectsViewModelBasedOnAcceptHeaderWhenInvokedAsFunctor()
    {
        $arr = [
                Model\JsonModel::class => [
                        'application/json',
                        'application/javascript'
                ],
                Model\FeedModel::class => [
                        'application/rss+xml',
                        'application/atom+xml'
                ],
                Model\ViewModel::class => '*/*'
        ];

        $plugin   = $this->plugin;
        $header   = Accept::fromString('Accept: application/rss+xml; version=0.2');
        $this->request->getHeaders()->addHeader($header);
        $result = $plugin($arr);

        $this->assertInstanceOf(Model\FeedModel::class, $result);
    }


    public function testInvokeWithoutDefaultsReturnsNullWhenNoMatchesOccur()
    {
        $arr = [
                Model\JsonModel::class => [
                        'application/json',
                        'application/javascript'
                ],
                Model\FeedModel::class => [
                        'application/rss+xml',
                        'application/atom+xml'
                ],
        ];

        $plugin   = $this->plugin;
        $header   = Accept::fromString('Accept: text/html; version=0.2');
        $this->request->getHeaders()->addHeader($header);

        $result = $plugin($arr, false);
        $this->assertNull($result);
    }

    public function testInvokeReturnsFieldValuePartOnMatchWhenReferenceProvided()
    {
        $plugin   = $this->plugin;
        $header   = Accept::fromString('Accept: text/html; version=0.2');
        $this->request->getHeaders()->addHeader($header);

        $ref = null;
        $result = $plugin([ Model\ViewModel::class => '*/*'], false, $ref);
        $this->assertInstanceOf(Model\ViewModel::class, $result);
        $this->assertNotInstanceOf(Model\JsonModel::class, $result);
        $this->assertNotInstanceOf(Model\FeedModel::class, $result);
        $this->assertInstanceOf(AcceptFieldValuePart::class, $ref);
    }

    public function testGetViewModelNameWithoutDefaults()
    {
        $arr = [
                Model\JsonModel::class => [
                        'application/json',
                        'application/javascript'
                ],
                Model\FeedModel::class => [
                        'application/rss+xml',
                        'application/atom+xml'
                ],
        ];

        $plugin   = $this->plugin;
        $header   = Accept::fromString('Accept: text/html; version=0.2');
        $this->request->getHeaders()->addHeader($header);

        $result = $plugin->getViewModelName($arr, false);
        $this->assertNull($result);

        $ref = null;
        $result = $plugin->getViewModelName([Model\ViewModel::class => '*/*'], false, $ref);
        $this->assertEquals(Model\ViewModel::class, $result);
        $this->assertInstanceOf(AcceptFieldValuePart::class, $ref);
    }

    public function testMatch()
    {
        $plugin   = $this->plugin;
        $header   = Accept::fromString('Accept: text/html; version=0.2');
        $this->request->getHeaders()->addHeader($header);

        $arr = [Model\ViewModel::class => '*/*'];
        $plugin->setDefaultMatchAgainst($arr);
        $this->assertEquals($plugin->getDefaultMatchAgainst(), $arr);
        $result = $plugin->match();
        $this->assertInstanceOf(AcceptFieldValuePart::class, $result);
        $this->assertEquals($plugin->getDefaultMatchAgainst(), $arr);
    }

    public function testInvalidModel()
    {
        $arr = ['DoesNotExist' => 'text/xml'];
        $header   = Accept::fromString('Accept: */*');
        $this->request->getHeaders()->addHeader($header);

        $this->expectException(InvalidArgumentException::class);

        $this->plugin->getViewModel($arr);
    }
}
