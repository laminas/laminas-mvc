<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Mvc\Service;

use Laminas\ServiceManager\Di\DiServiceInitializerFactory as OriginalFactory;

/**
 * Since 2.7.9, this class now extends the version defined in laminas-servicemanager-di,
 * ensuring backwards compatibility with laminas-servicemanger v2 and forwards
 * compatibility with laminas-servicemanager v3.
 *
 * @deprecated Since 2.7.9. The factory is now defined in laminas-servicemanager-di,
 *     and removed in 3.0.0. Use Laminas\ServiceManager\Di\DiServiceInitializerFactory
 *     from laminas-servicemanager-di instead if you rely on this feature.
 */
class DiServiceInitializerFactory extends OriginalFactory
{
}
