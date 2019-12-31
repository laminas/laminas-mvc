<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Mvc\Service;

use Laminas\ServiceManager\ServiceLocatorInterface;

class SerializerAdapterPluginManagerFactory extends AbstractPluginManagerFactory
{
    const PLUGIN_MANAGER_CLASS = 'Laminas\Serializer\AdapterPluginManager';


    /**
     * {@inheritDoc}
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        /** @var $serviceListener \Laminas\ModuleManager\Listener\ServiceListener */
        $serviceListener = $serviceLocator->get('ServiceListener');

        // This will allow to register new serializers easily, either by implementing the SerializerProviderInterface
        // in your Module.php file, or by adding the "serializers" key in your module.config.php file
        $serviceListener->addServiceManager(
            $serviceLocator,
            'serializers',
            'Laminas\ModuleManager\Feature\SerializerProviderInterface',
            'getSerializerConfig'
        );

        return parent::createService($serviceLocator);
    }
}
