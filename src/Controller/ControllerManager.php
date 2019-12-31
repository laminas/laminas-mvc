<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Mvc\Controller;

use Interop\Container\ContainerInterface;
use Laminas\EventManager\EventManagerAwareInterface;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\Mvc\Exception;
use Laminas\ServiceManager\AbstractPluginManager;
use Laminas\ServiceManager\ConfigInterface;
use Laminas\ServiceManager\Exception\InvalidServiceException;
use Laminas\Stdlib\DispatchableInterface;

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
     * @param  array $v3config
     */
    public function __construct($configOrContainerInstance, array $v3config = [])
    {
        $this->addInitializer([$this, 'injectEventManager']);
        $this->addInitializer([$this, 'injectPluginManager']);
        parent::__construct($configOrContainerInstance, $v3config);
    }

    /**
     * Validate a plugin (v3)
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
     * Validate a plugin (v2)
     *
     * {@inheritDoc}
     *
     * @throws Exception\InvalidControllerException
     */
    public function validatePlugin($plugin)
    {
        try {
            $this->validate($plugin);
        } catch (InvalidServiceException $e) {
            throw new Exception\InvalidControllerException(
                $e->getMessage(),
                $e->getCode(),
                $e
            );
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
     * @param ContainerInterface|DispatchableInterface $first Container when
     *     using laminas-servicemanager v3; controller under v2.
     * @param DispatchableInterface|ContainerInterface $second Controller when
     *     using laminas-servicemanager v3; container under v2.
     */
    public function injectEventManager($first, $second)
    {
        if ($first instanceof ContainerInterface) {
            $container = $first;
            $controller = $second;
        } else {
            $container = $second;
            $controller = $first;
        }

        if (! $controller instanceof EventManagerAwareInterface) {
            return;
        }

        $events = $controller->getEventManager();
        if (! $events || ! $events->getSharedManager() instanceof SharedEventManagerInterface) {
            // For v2, we need to pull the parent service locator
            if (! method_exists($container, 'configure')) {
                $container = $container->getServiceLocator() ?: $container;
            }

            $controller->setEventManager($container->get('EventManager'));
        }
    }

    /**
     * Initializer: inject plugin manager
     *
     * @param ContainerInterface|DispatchableInterface $first Container when
     *     using laminas-servicemanager v3; controller under v2.
     * @param DispatchableInterface|ContainerInterface $second Controller when
     *     using laminas-servicemanager v3; container under v2.
     */
    public function injectPluginManager($first, $second)
    {
        if ($first instanceof ContainerInterface) {
            $container = $first;
            $controller = $second;
        } else {
            $container = $second;
            $controller = $first;
        }

        if (! method_exists($controller, 'setPluginManager')) {
            return;
        }

        // For v2, we need to pull the parent service locator
        if (! method_exists($container, 'configure')) {
            $container = $container->getServiceLocator() ?: $container;
        }

        $controller->setPluginManager($container->get('ControllerPluginManager'));
    }
}
