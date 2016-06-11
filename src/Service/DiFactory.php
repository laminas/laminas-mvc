<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Mvc\Service;

use Zend\ServiceManager\Di\DiFactory as OriginalFactory;

/**
 * Since 2.7.9, this class now extends the version defined in zend-servicemanager-di,
 * ensuring backwards compatibility with zend-servicemanger v2 and forwards
 * compatibility with zend-servicemanager v3.
 *
 * @deprecated Since 2.7.9. The factory is now defined in zend-servicemanager-di,
 *     and removed in 3.0.0. Use Zend\ServiceManager\Di\DiFactory from
 *     zend-servicemanager-di instead if you rely on this feature.
 */
class DiFactory extends OriginalFactory
{
}
