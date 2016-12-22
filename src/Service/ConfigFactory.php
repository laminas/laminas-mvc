<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
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
