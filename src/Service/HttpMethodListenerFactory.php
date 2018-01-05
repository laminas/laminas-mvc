<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Mvc\Service;

use Interop\Container\ContainerInterface;
use Zend\Mvc\HttpMethodListener;
use Zend\ServiceManager\Factory\FactoryInterface;

class HttpMethodListenerFactory implements FactoryInterface
{
    /**
     * {@inheritdoc}
     * @return HttpMethodListener
     */
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        $config = $container->get('config');

        if (! isset($config['http_methods_listener'])) {
            return new HttpMethodListener();
        }

        $listenerConfig  = $config['http_methods_listener'];
        $enabled = array_key_exists('enabled', $listenerConfig)
            ? $listenerConfig['enabled']
            : true;
        $allowedMethods = (isset($listenerConfig['allowed_methods']) && is_array($listenerConfig['allowed_methods']))
            ? $listenerConfig['allowed_methods']
            : null;

        return new HttpMethodListener($enabled, $allowedMethods);
    }
}
