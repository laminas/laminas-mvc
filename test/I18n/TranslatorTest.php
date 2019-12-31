<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\I18n;

use Laminas\Mvc\I18n\Translator;
use PHPUnit_Framework_TestCase as TestCase;

class TranslatorTest extends TestCase
{
    public function setUp()
    {
        $this->i18nTranslator = $this->getMock('Laminas\I18n\Translator\Translator');
        $this->translator = new Translator($this->i18nTranslator);
    }

    public function testIsAnI18nTranslator()
    {
        $this->assertInstanceOf('Laminas\I18n\Translator\TranslatorInterface', $this->translator);
    }

    public function testIsAValidatorTranslator()
    {
        $this->assertInstanceOf('Laminas\Validator\Translator\TranslatorInterface', $this->translator);
    }

    public function testCanRetrieveComposedTranslator()
    {
        $this->assertSame($this->i18nTranslator, $this->translator->getTranslator());
    }

    public function testCanProxyToComposedTranslatorMethods()
    {
        $this->i18nTranslator->expects($this->once())
            ->method('setLocale')
            ->with($this->equalTo('en_US'));
        $this->translator->setLocale('en_US');
    }
}
