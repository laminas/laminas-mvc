<?php

declare(strict_types=1);

namespace Laminas\Mvc\Controller;

use Laminas\Mvc\Controller\Plugin\PluginInterface;
use Laminas\ServiceManager\AbstractPluginManager;
use Laminas\ServiceManager\Exception\InvalidServiceException;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\Stdlib\DispatchableInterface;
use Zend\Mvc\Controller\Plugin\AcceptableViewModelSelector;
use Zend\Mvc\Controller\Plugin\CreateHttpNotFoundModel;
use Zend\Mvc\Controller\Plugin\Forward;
use Zend\Mvc\Controller\Plugin\Layout;
use Zend\Mvc\Controller\Plugin\Params;
use Zend\Mvc\Controller\Plugin\Redirect;
use Zend\Mvc\Controller\Plugin\Url;

use function gettype;
use function is_object;
use function method_exists;
use function sprintf;

/**
 * Plugin manager implementation for controllers
 *
 * Registers a number of default plugins, and contains an initializer for
 * injecting plugins with the current controller.
 *
 * @extends AbstractPluginManager<PluginInterface>
 */
class PluginManager extends AbstractPluginManager
{
    /**
     * Plugins must be of this type.
     *
     * @var class-string
     */
    protected $instanceOf = PluginInterface::class;

    /** @var string[] Default aliases */
    protected $aliases = [
        'AcceptableViewModelSelector' => Plugin\AcceptableViewModelSelector::class,
        'acceptableViewModelSelector' => Plugin\AcceptableViewModelSelector::class,
        'acceptableviewmodelselector' => Plugin\AcceptableViewModelSelector::class,
        'Forward'                     => Plugin\Forward::class,
        'forward'                     => Plugin\Forward::class,
        'Layout'                      => Plugin\Layout::class,
        'layout'                      => Plugin\Layout::class,
        'Params'                      => Plugin\Params::class,
        'params'                      => Plugin\Params::class,
        'Redirect'                    => Plugin\Redirect::class,
        'redirect'                    => Plugin\Redirect::class,
        'Url'                         => Plugin\Url::class,
        'url'                         => Plugin\Url::class,
        'CreateHttpNotFoundModel'     => Plugin\CreateHttpNotFoundModel::class,
        'createHttpNotFoundModel'     => Plugin\CreateHttpNotFoundModel::class,
        'createhttpnotfoundmodel'     => Plugin\CreateHttpNotFoundModel::class,

        // Legacy Zend Framework aliases
        Forward::class                     => Plugin\Forward::class,
        AcceptableViewModelSelector::class => Plugin\AcceptableViewModelSelector::class,
        Layout::class                      => Plugin\Layout::class,
        Params::class                      => Plugin\Params::class,
        Redirect::class                    => Plugin\Redirect::class,
        Url::class                         => Plugin\Url::class,
        CreateHttpNotFoundModel::class     => Plugin\CreateHttpNotFoundModel::class,

        // v2 normalized FQCNs
        'zendmvccontrollerpluginforward'                     => Plugin\Forward::class,
        'zendmvccontrollerpluginacceptableviewmodelselector' => Plugin\AcceptableViewModelSelector::class,
        'zendmvccontrollerpluginlayout'                      => Plugin\Layout::class,
        'zendmvccontrollerpluginparams'                      => Plugin\Params::class,
        'zendmvccontrollerpluginredirect'                    => Plugin\Redirect::class,
        'zendmvccontrollerpluginurl'                         => Plugin\Url::class,
        'zendmvccontrollerplugincreatehttpnotfoundmodel'     => Plugin\CreateHttpNotFoundModel::class,
    ];

    /** @var string[]|callable[] Default factories */
    protected $factories = [
        Plugin\Forward::class                     => Plugin\Service\ForwardFactory::class,
        Plugin\AcceptableViewModelSelector::class => InvokableFactory::class,
        Plugin\Layout::class                      => InvokableFactory::class,
        Plugin\Params::class                      => InvokableFactory::class,
        Plugin\Redirect::class                    => InvokableFactory::class,
        Plugin\Url::class                         => InvokableFactory::class,
        Plugin\CreateHttpNotFoundModel::class     => InvokableFactory::class,

        // v2 normalized names
        'laminasmvccontrollerpluginforward'                     => Plugin\Service\ForwardFactory::class,
        'laminasmvccontrollerpluginacceptableviewmodelselector' => InvokableFactory::class,
        'laminasmvccontrollerpluginlayout'                      => InvokableFactory::class,
        'laminasmvccontrollerpluginparams'                      => InvokableFactory::class,
        'laminasmvccontrollerpluginredirect'                    => InvokableFactory::class,
        'laminasmvccontrollerpluginurl'                         => InvokableFactory::class,
        'laminasmvccontrollerplugincreatehttpnotfoundmodel'     => InvokableFactory::class,
    ];

    protected ?DispatchableInterface $controller = null;

    /**
     * Retrieve a registered instance
     *
     * After the plugin is retrieved from the service locator, inject the
     * controller in the plugin every time it is requested. This is required
     * because a controller can use a plugin and another controller can be
     * dispatched afterwards. If this second controller uses the same plugin
     * as the first controller, the reference to the controller inside the
     * plugin is lost.
     *
     * @inheritDoc
     */
    public function get($name, ?array $options = null)
    {
        $plugin = parent::get($name, $options);
        $this->injectController($plugin);

        return $plugin;
    }

    /**
     * Set controller
     *
     * @return PluginManager
     */
    public function setController(DispatchableInterface $controller)
    {
        $this->controller = $controller;

        return $this;
    }

    /**
     * Retrieve controller instance
     *
     * @return null|DispatchableInterface
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * Inject a helper instance with the registered controller
     *
     * @param  object $plugin
     * @return void
     */
    public function injectController($plugin)
    {
        if (! is_object($plugin)) {
            return;
        }
        if (! method_exists($plugin, 'setController')) {
            return;
        }

        $controller = $this->getController();
        if (! $controller instanceof DispatchableInterface) {
            return;
        }

        $plugin->setController($controller);
    }

    /**
     * Validate a plugin
     *
     * {@inheritDoc}
     */
    public function validate($instance): void
    {
        if (! $instance instanceof $this->instanceOf) {
            throw new InvalidServiceException(sprintf(
                'Plugin of type "%s" is invalid; must implement %s',
                is_object($instance) ? $instance::class : gettype($instance),
                $this->instanceOf
            ));
        }
    }
}
