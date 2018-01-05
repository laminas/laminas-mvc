<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Mvc\Service;

use Interop\Container\ContainerInterface;
use Traversable;
use Zend\ServiceManager\Factory\FactoryInterface;

class ConfigFactory implements FactoryInterface
{
    /**
     * Create the application configuration service
     *
     * Retrieves the Module Manager from the service locator, and executes
     * {@link Zend\ModuleManager\ModuleManager::loadModules()}.
     *
     * It then retrieves the config listener from the module manager, and from
     * that the merged configuration.
     *
     * @param ContainerInterface $container
     * @param string $name
     * @param null|array $options
     * @return array|Traversable
     */
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        $moduleManager = $container->get('ModuleManager');
        $moduleManager->loadModules();
        $moduleParams = $moduleManager->getEvent()->getParams();
        return $moduleParams['configListener']->getMergedConfig(false);
    }
}
