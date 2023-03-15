<?php

namespace LaminasTest\Mvc\Controller\Plugin;

use Laminas\Mvc\Controller\Plugin\AcceptableViewModelSelector;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\FeedModel;
use Laminas\View\Model\ViewModel;
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
    private Request $request;
    private MvcEvent $event;
    private SampleController $controller;
    private AcceptableViewModelSelector $plugin;

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
            JsonModel::class => [
                'application/json',
                'application/javascript'
            ],
            FeedModel::class => [
                'application/rss+xml',
                'application/atom+xml'
            ],
            ViewModel::class => '*/*'
        ];

        $header   = Accept::fromString(
            'Accept: text/plain; q=0.5, text/html, text/xml; q=0, text/x-dvi; q=0.8, text/x-c'
        );
        $this->request->getHeaders()->addHeader($header);
        $plugin   = $this->plugin;
        $plugin->setDefaultViewModelName(FeedModel::class);
        $result   = $plugin($arr);

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertNotInstanceOf(FeedModel::class, $result); // Ensure the default wasn't selected
        $this->assertNotInstanceOf(JsonModel::class, $result);
    }

    public function testDefaultViewModelName()
    {
        $arr = [
            JsonModel::class => [
                'application/json',
                'application/javascript'
            ],
            FeedModel::class => [
                'application/rss+xml',
                'application/atom+xml'
            ],
        ];

        $header   = Accept::fromString('Accept: text/plain');
        $this->request->getHeaders()->addHeader($header);
        $plugin   = $this->plugin;
        $result   = $plugin->getViewModelName($arr);

        $this->assertEquals(ViewModel::class, $result); //   Default Default View Model Name

        $plugin->setDefaultViewModelName(FeedModel::class);
        $this->assertEquals($plugin->getDefaultViewModelName(), FeedModel::class); // Test getter along the way
        $this->assertInstanceOf(FeedModel::class, $plugin($arr));
    }

    public function testSelectsViewModelBasedOnAcceptHeaderWhenInvokedAsFunctor()
    {
        $arr = [
                JsonModel::class => [
                        'application/json',
                        'application/javascript'
                ],
                FeedModel::class => [
                        'application/rss+xml',
                        'application/atom+xml'
                ],
                ViewModel::class => '*/*'
        ];

        $plugin   = $this->plugin;
        $header   = Accept::fromString('Accept: application/rss+xml; version=0.2');
        $this->request->getHeaders()->addHeader($header);
        $result = $plugin($arr);

        $this->assertInstanceOf(FeedModel::class, $result);
    }


    public function testInvokeWithoutDefaultsReturnsNullWhenNoMatchesOccur()
    {
        $arr = [
                JsonModel::class => [
                        'application/json',
                        'application/javascript'
                ],
                FeedModel::class => [
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
        $result = $plugin([ ViewModel::class => '*/*'], false, $ref);
        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertNotInstanceOf(JsonModel::class, $result);
        $this->assertNotInstanceOf(FeedModel::class, $result);
        $this->assertInstanceOf(AcceptFieldValuePart::class, $ref);
    }

    public function testGetViewModelNameWithoutDefaults()
    {
        $arr = [
                JsonModel::class => [
                        'application/json',
                        'application/javascript'
                ],
                FeedModel::class => [
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
        $result = $plugin->getViewModelName([ViewModel::class => '*/*'], false, $ref);
        $this->assertEquals(ViewModel::class, $result);
        $this->assertInstanceOf(AcceptFieldValuePart::class, $ref);
    }

    public function testMatch()
    {
        $plugin   = $this->plugin;
        $header   = Accept::fromString('Accept: text/html; version=0.2');
        $this->request->getHeaders()->addHeader($header);

        $arr = [ViewModel::class => '*/*'];
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
