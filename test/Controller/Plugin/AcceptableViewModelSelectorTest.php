<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Controller\Plugin;

use Laminas\Http\Header\Accept;
use Laminas\Http\Request;
use Laminas\Mvc\Controller\Plugin\AcceptableViewModelSelector;
use Laminas\Mvc\MvcEvent;
use LaminasTest\Mvc\Controller\TestAsset\SampleController;

class AcceptableViewModelSelectorTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
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
        $arr = array(
            'Laminas\View\Model\JsonModel' => array(
                'application/json',
                'application/javascript'
            ),
            'Laminas\View\Model\FeedModel' => array(
                'application/rss+xml',
                'application/atom+xml'
            ),
            'Laminas\View\Model\ViewModel' => '*/*'
        );

        $header   = Accept::fromString('Accept: text/plain; q=0.5, text/html, text/xml; q=0, text/x-dvi; q=0.8, text/x-c');
        $this->request->getHeaders()->addHeader($header);
        $plugin   = $this->plugin;
        $plugin->setDefaultViewModelName('Laminas\View\Model\FeedModel');
        $result   = $plugin($arr);

        $this->assertInstanceOf('Laminas\View\Model\ViewModel', $result);
        $this->assertNotInstanceOf('Laminas\View\Model\FeedModel', $result); // Ensure the default wasn't selected
        $this->assertNotInstanceOf('Laminas\View\Model\JsonModel', $result);
    }

    public function testDefaultViewModelName()
    {
        $arr = array(
            'Laminas\View\Model\JsonModel' => array(
                'application/json',
                'application/javascript'
            ),
            'Laminas\View\Model\FeedModel' => array(
                'application/rss+xml',
                'application/atom+xml'
            ),
        );

        $header   = Accept::fromString('Accept: text/plain');
        $this->request->getHeaders()->addHeader($header);
        $plugin   = $this->plugin;
        $result   = $plugin->getViewModelName($arr);

        $this->assertEquals('Laminas\View\Model\ViewModel', $result); //   Default Default View Model Name

        $plugin->setDefaultViewModelName('Laminas\View\Model\FeedModel');
        $this->assertEquals($plugin->getDefaultViewModelName(), 'Laminas\View\Model\FeedModel'); // Test getter along the way
        $this->assertInstanceOf('Laminas\View\Model\FeedModel', $plugin($arr));
    }

    public function testSelectsViewModelBasedOnAcceptHeaderWhenInvokedAsFunctor()
    {
        $arr = array(
                'Laminas\View\Model\JsonModel' => array(
                        'application/json',
                        'application/javascript'
                ),
                'Laminas\View\Model\FeedModel' => array(
                        'application/rss+xml',
                        'application/atom+xml'
                ),
                'Laminas\View\Model\ViewModel' => '*/*'
        );

        $plugin   = $this->plugin;
        $header   = Accept::fromString('Accept: application/rss+xml; version=0.2');
        $this->request->getHeaders()->addHeader($header);
        $result = $plugin($arr);

        $this->assertInstanceOf('Laminas\View\Model\FeedModel', $result);
    }


    public function testInvokeWithoutDefaultsReturnsNullWhenNoMatchesOccur()
    {
        $arr = array(
                'Laminas\View\Model\JsonModel' => array(
                        'application/json',
                        'application/javascript'
                ),
                'Laminas\View\Model\FeedModel' => array(
                        'application/rss+xml',
                        'application/atom+xml'
                ),
        );

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
        $result = $plugin(array( 'Laminas\View\Model\ViewModel' => '*/*'), false, $ref);
        $this->assertInstanceOf('Laminas\View\Model\ViewModel', $result);
        $this->assertNotInstanceOf('Laminas\View\Model\JsonModel', $result);
        $this->assertNotInstanceOf('Laminas\View\Model\FeedModel', $result);
        $this->assertInstanceOf('Laminas\Http\Header\Accept\FieldValuePart\AcceptFieldValuePart', $ref);
    }

    public function testGetViewModelNameWithoutDefaults()
    {
        $arr = array(
                'Laminas\View\Model\JsonModel' => array(
                        'application/json',
                        'application/javascript'
                ),
                'Laminas\View\Model\FeedModel' => array(
                        'application/rss+xml',
                        'application/atom+xml'
                ),
        );

        $plugin   = $this->plugin;
        $header   = Accept::fromString('Accept: text/html; version=0.2');
        $this->request->getHeaders()->addHeader($header);

        $result = $plugin->getViewModelName($arr, false);
        $this->assertNull($result);

        $ref = null;
        $result = $plugin->getViewModelName(array( 'Laminas\View\Model\ViewModel' => '*/*'), false, $ref);
        $this->assertEquals('Laminas\View\Model\ViewModel', $result);
        $this->assertInstanceOf('Laminas\Http\Header\Accept\FieldValuePart\AcceptFieldValuePart', $ref);
    }

    public function testMatch()
    {
        $plugin   = $this->plugin;
        $header   = Accept::fromString('Accept: text/html; version=0.2');
        $this->request->getHeaders()->addHeader($header);

        $arr = array( 'Laminas\View\Model\ViewModel' => '*/*');
        $plugin->setDefaultMatchAgainst($arr);
        $this->assertEquals($plugin->getDefaultMatchAgainst(), $arr);
        $result = $plugin->match();
        $this->assertInstanceOf(
                'Laminas\Http\Header\Accept\FieldValuePart\AcceptFieldValuePart',
                $result
        );
        $this->assertEquals($plugin->getDefaultMatchAgainst(), $arr);
    }

    public function testInvalidModel()
    {
        $arr = array('DoesNotExist' => 'text/xml');
        $header   = Accept::fromString('Accept: */*');
        $this->request->getHeaders()->addHeader($header);

        $this->setExpectedException('\Laminas\Mvc\Exception\InvalidArgumentException');

        $this->plugin->getViewModel($arr);
    }
}
