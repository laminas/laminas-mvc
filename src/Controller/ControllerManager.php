<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Mvc\Controller;

use Interop\Container\ContainerInterface;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\ConfigInterface;
use Zend\ServiceManager\Exception\InvalidServiceException;
use Zend\Stdlib\DispatchableInterface;

/**
 * Manager for loading controllers
 *
 * Does not define any controllers by default, but does add a validator.
 */
class ControllerManager extends AbstractPluginManager
{
    /**
     * We do not want arbitrary classes instantiated as controllers.
     *
     * @var bool
     */
    protected $autoAddInvokableClass = false;

    /**
     * Controllers must be of this type.
     *
     * @var string
     */
    protected $instanceOf = DispatchableInterface::class;

    /**
     * Constructor
     *
     * Injects an initializer for injecting controllers with an
     * event manager and plugin manager.
     *
     * @param  ConfigInterface|ContainerInterface $container
     * @param  array $config
     */
    public function __construct($configOrContainerInstance, array $config = [])
    {
        $this->addInitializer([$this, 'injectEventManager']);
        $this->addInitializer([$this, 'injectPluginManager']);
        parent::__construct($configOrContainerInstance, $config);
    }

    /**
     * Validate a plugin
     *
     * {@inheritDoc}
     */
    public function validate($plugin)
    {
        if (! $plugin instanceof $this->instanceOf) {
            throw new InvalidServiceException(sprintf(
                'Plugin of type "%s" is invalid; must implement %s',
                (is_object($plugin) ? get_class($plugin) : gettype($plugin)),
                $this->instanceOf
            ));
        }
    }

    /**
     * Initializer: inject EventManager instance
     *
     * If we have an event manager composed already, make sure it gets injected
     * with the shared event manager.
     *
     * The AbstractController lazy-instantiates an EM instance, which is why
     * the shared EM injection needs to happen; the conditional will always
     * pass.
     *
     * @param ContainerInterface $container
     * @param DispatchableInterface $controller
     */
    public function injectEventManager(ContainerInterface $container, $controller)
    {
        if (! $controller instanceof EventManagerAwareInterface) {
            return;
        }

        $events = $controller->getEventManager();
        if (! $events || ! $events->getSharedManager() instanceof SharedEventManagerInterface) {
            $controller->setEventManager($container->get('EventManager'));
        }
    }

    /**
     * Initializer: inject plugin manager
     *
     * @param ContainerInterface $container
     * @param DispatchableInterface $controller
     */
    public function injectPluginManager(ContainerInterface $container, $controller)
    {
        if (! method_exists($controller, 'setPluginManager')) {
            return;
        }

        $controller->setPluginManager($container->get('ControllerPluginManager'));
    }
}
