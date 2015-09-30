<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Mvc\Controller;

use Zend\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\Factory\InvokableFactory;
use Zend\Stdlib\DispatchableInterface;

/**
 * Plugin manager implementation for controllers
 *
 * Registers a number of default plugins, and contains an initializer for
 * injecting plugins with the current controller.
 */
class PluginManager extends AbstractPluginManager
{
    protected $instanceOf = Plugin\PluginInterface::class;

    /**
     * @var string[] Default aliases
     */
    protected $aliases = [
        'AcceptableViewModelSelector' => Plugin\AcceptableViewModelSelector::class,
        'acceptableViewModelSelector' => Plugin\AcceptableViewModelSelector::class,
        'acceptableviewmodelselector' => Plugin\AcceptableViewModelSelector::class,
        'FilePostRedirectGet'         => Plugin\FilePostRedirectGet::class,
        'filePostRedirectGet'         => Plugin\FilePostRedirectGet::class,
        'filepostredirectget'         => Plugin\FilePostRedirectGet::class,
        'fileprg'                     => Plugin\FilePostRedirectGet::class,
        'FlashMessenger'              => Plugin\FlashMessenger::class,
        'flashMessenger'              => Plugin\FlashMessenger::class,
        'flashmessenger'              => Plugin\FlashMessenger::class,
        'Forward'                     => Plugin\Forward::class,
        'forward'                     => Plugin\Forward::class,
        'Identity'                    => Plugin\Identity::class,
        'identity'                    => Plugin\Identity::class,
        'Layout'                      => Plugin\Layout::class,
        'layout'                      => Plugin\Layout::class,
        'Params'                      => Plugin\Params::class,
        'params'                      => Plugin\Params::class,
        'PostRedirectGet'             => Plugin\PostRedirectGet::class,
        'postRedirectGet'             => Plugin\PostRedirectGet::class,
        'postredirectget'             => Plugin\PostRedirectGet::class,
        'prg'                         => Plugin\PostRedirectGet::class,
        'Redirect'                    => Plugin\Redirect::class,
        'redirect'                    => Plugin\Redirect::class,
        'Url'                         => Plugin\Url::class,
        'url'                         => Plugin\Url::class,
        'CreateHttpNotFoundModel'     => Plugin\CreateHttpNotFoundModel::class,
        'createHttpNotFoundModel'     => Plugin\CreateHttpNotFoundModel::class,
        'createhttpnotfoundmodel'     => Plugin\CreateHttpNotFoundModel::class,
        'CreateConsoleNotFoundModel'  => Plugin\CreateConsoleNotFoundModel::class,
        'createConsoleNotFoundModel'  => Plugin\CreateConsoleNotFoundModel::class,
        'createconsolenotfoundmodel'  => Plugin\CreateConsoleNotFoundModel::class,
    ];

    /**
     * @var string[]|callable[] Default factories
     */
    protected $factories = [
        Plugin\Forward::class                     => Plugin\Service\ForwardFactory::class,
        Plugin\Identity::class                    => Plugin\Service\IdentityFactory::class,
        Plugin\AcceptableViewModelSelector::class => InvokableFactory::class,
        Plugin\FilePostRedirectGet::class         => InvokableFactory::class,
        Plugin\FlashMessenger::class              => InvokableFactory::class,
        Plugin\Layout::class                      => InvokableFactory::class,
        Plugin\Params::class                      => InvokableFactory::class,
        Plugin\PostRedirectGet::class             => InvokableFactory::class,
        Plugin\Redirect::class                    => InvokableFactory::class,
        Plugin\Url::class                         => InvokableFactory::class,
        Plugin\CreateHttpNotFoundModel::class     => InvokableFactory::class,
        Plugin\CreateConsoleNotFoundModel::class  => InvokableFactory::class,
    ];

    /**
     * @var DispatchableInterface
     */
    protected $controller;

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
     * @param  string $name
     * @return DispatchableInterface
     */
    public function get($name, array $options = null)
    {
        $plugin = parent::get($name, $options);
        $this->injectController($plugin);

        return $plugin;
    }

    /**
     * Set controller
     *
     * @param  DispatchableInterface $controller
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
        if (!is_object($plugin)) {
            return;
        }
        if (!method_exists($plugin, 'setController')) {
            return;
        }

        $controller = $this->getController();
        if (!$controller instanceof DispatchableInterface) {
            return;
        }

        $plugin->setController($controller);
    }
}
