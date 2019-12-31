<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Mvc\Service;

use Laminas\I18n\Translator\TranslatorServiceFactory as I18nTranslatorServiceFactory;
use Laminas\Mvc\I18n\Translator;
use Laminas\ServiceManager\ServiceLocatorInterface;

/**
 * Overrides the translator factory from the i18n component in order to
 * replace it with the bridge class from this namespace.
 */
class TranslatorServiceFactory extends I18nTranslatorServiceFactory
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        // Configure the translator
        $config     = $serviceLocator->get('Config');
        $trConfig   = isset($config['translator']) ? $config['translator'] : array();
        $translator = Translator::factory($trConfig);
        return $translator;
    }
}
