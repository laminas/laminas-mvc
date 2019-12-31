<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Mvc\Service;

use Laminas\I18n\Translator\LoaderPluginManager as TranslatorLoaderPluginManager;

class TranslatorPluginManagerFactory extends AbstractPluginManagerFactory
{
    const PLUGIN_MANAGER_CLASS = TranslatorLoaderPluginManager::class;
}
