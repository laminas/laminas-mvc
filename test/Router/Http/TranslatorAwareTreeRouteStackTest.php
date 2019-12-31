<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Router\Http;

use Laminas\Http\Request as Request;
use Laminas\I18n\Translator\Translator;
use Laminas\Mvc\Router\Http\TranslatorAwareTreeRouteStack;
use Laminas\Uri\Http as HttpUri;
use PHPUnit_Framework_TestCase as TestCase;

class TranslatorAwareTreeRouteStackTest extends TestCase
{
    public function testTranslatorAwareInterfaceImplementation()
    {
        $stack = new TranslatorAwareTreeRouteStack();
        $this->assertInstanceOf('Laminas\I18n\Translator\TranslatorAwareInterface', $stack);

        // Defaults
        $this->assertNull($stack->getTranslator());
        $this->assertFalse($stack->hasTranslator());
        $this->assertEquals('default', $stack->getTranslatorTextDomain());
        $this->assertTrue($stack->isTranslatorEnabled());

        // Inject translator without text domain
        $translator = new Translator();
        $stack->setTranslator($translator);
        $this->assertSame($translator, $stack->getTranslator());
        $this->assertEquals('default', $stack->getTranslatorTextDomain());
        $this->assertTrue($stack->hasTranslator());

        // Reset translator
        $stack->setTranslator(null);
        $this->assertNull($stack->getTranslator());
        $this->assertFalse($stack->hasTranslator());

        // Inject translator with text domain
        $stack->setTranslator($translator, 'alternative');
        $this->assertSame($translator, $stack->getTranslator());
        $this->assertEquals('alternative', $stack->getTranslatorTextDomain());

        // Set text domain
        $stack->setTranslatorTextDomain('default');
        $this->assertEquals('default', $stack->getTranslatorTextDomain());

        // Disable translator
        $stack->setTranslatorEnabled(false);
        $this->assertFalse($stack->isTranslatorEnabled());
    }

    public function testTranslatorIsPassedThroughMatchMethod()
    {
        $translator = new Translator();
        $request    = new Request();

        $route = $this->getMock('Laminas\Mvc\Router\Http\RouteInterface');
        $route->expects($this->once())
              ->method('match')
              ->with(
                  $this->equalTo($request),
                  $this->isNull(),
                  $this->equalTo(array('translator' => $translator, 'text_domain' => 'default'))
              );

        $stack = new TranslatorAwareTreeRouteStack();
        $stack->addRoute('test', $route);

        $stack->match($request, null, array('translator' => $translator));
    }

    public function testTranslatorIsPassedThroughAssembleMethod()
    {
        $translator = new Translator();
        $uri        = new HttpUri();

        $route = $this->getMock('Laminas\Mvc\Router\Http\RouteInterface');
        $route->expects($this->once())
              ->method('assemble')
              ->with(
                  $this->equalTo(array()),
                  $this->equalTo(array('translator' => $translator, 'text_domain' => 'default', 'uri' => $uri))
              );

        $stack = new TranslatorAwareTreeRouteStack();
        $stack->addRoute('test', $route);

        $stack->assemble(array(), array('name' => 'test', 'translator' => $translator, 'uri' => $uri));
    }
}
