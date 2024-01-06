<?php

declare(strict_types=1);

namespace Laminas\Mvc\View\Http;

use ArrayAccess;
use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\ListenerAggregateInterface;
use Laminas\Mvc\MvcEvent;
use Laminas\Mvc\View\Http\InjectTemplateListener;
use Laminas\Stdlib\DispatchableInterface;
use Laminas\View\Model\ModelInterface;
use Laminas\View\View;
use Psr\Container\ContainerInterface;
use Traversable;

use function is_array;
use function is_string;

/**
 * Prepares the view layer
 *
 * Instantiates and configures all classes related to the view layer, including
 * the renderer (and its associated resolver(s) and helper manager), the view
 * object (and its associated rendering strategies), and the various MVC
 * strategies and listeners.
 *
 * Defines and manages the following services:
 *
 * - ViewHelperManager (also aliased to Laminas\View\HelperPluginManager)
 * - ViewTemplateMapResolver (also aliased to Laminas\View\Resolver\TemplateMapResolver)
 * - ViewTemplatePathStack (also aliased to Laminas\View\Resolver\TemplatePathStack)
 * - ViewResolver (also aliased to Laminas\View\Resolver\AggregateResolver and ResolverInterface)
 * - ViewRenderer (also aliased to Laminas\View\Renderer\PhpRenderer and RendererInterface)
 * - ViewPhpRendererStrategy (also aliased to Laminas\View\Strategy\PhpRendererStrategy)
 * - View (also aliased to Laminas\View\View)
 * - DefaultRenderingStrategy (also aliased to Laminas\Mvc\View\Http\DefaultRenderingStrategy)
 * - ExceptionStrategy (also aliased to Laminas\Mvc\View\Http\ExceptionStrategy)
 * - RouteNotFoundStrategy (also aliased to Laminas\Mvc\View\Http\RouteNotFoundStrategy and 404Strategy)
 * - ViewModel
 */
class ViewManager extends AbstractListenerAggregate
{
    /** @var array application configuration service */
    protected $config;

    /** @var MvcEvent */
    protected $event;

    protected ContainerInterface $services;

    protected ?View $view = null;

    protected ?ModelInterface $viewModel = null;

    public function __construct(
        ContainerInterface $container
    ) {
        $this->services = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_BOOTSTRAP, [$this, 'onBootstrap'], 10000);
    }

    /**
     * Prepares the view layer
     */
    public function onBootstrap(MvcEvent $event): void
    {
        $application  = $event->getApplication();
        $config       = $this->services->get('config');
        $events       = $application->getEventManager();
        $sharedEvents = $events->getSharedManager();

        $this->config = isset($config['view_manager'])
            && (is_array($config['view_manager'])
            || $config['view_manager'] instanceof ArrayAccess)
                ? $config['view_manager']
                : [];
        $this->event  = $event;

        $routeNotFoundStrategy = $this->services->get('HttpRouteNotFoundStrategy');
        $exceptionStrategy     = $this->services->get('HttpExceptionStrategy');
        $mvcRenderingStrategy  = $this->services->get('HttpDefaultRenderingStrategy');

        $this->injectViewModelIntoPlugin();

        $injectTemplateListener  = $this->services->get(InjectTemplateListener::class);
        $createViewModelListener = new CreateViewModelListener();
        $injectViewModelListener = new InjectViewModelListener();

        $this->registerMvcRenderingStrategies($events);
        $this->registerViewStrategies();

        $routeNotFoundStrategy->attach($events);
        $exceptionStrategy->attach($events);
        $events->attach(MvcEvent::EVENT_DISPATCH_ERROR, [$injectViewModelListener, 'injectViewModel'], -100);
        $events->attach(MvcEvent::EVENT_RENDER_ERROR, [$injectViewModelListener, 'injectViewModel'], -100);
        $mvcRenderingStrategy->attach($events);

        $sharedEvents->attach(
            DispatchableInterface::class,
            MvcEvent::EVENT_DISPATCH,
            [$createViewModelListener, 'createViewModelFromArray'],
            -80
        );
        $sharedEvents->attach(
            DispatchableInterface::class,
            MvcEvent::EVENT_DISPATCH,
            [$routeNotFoundStrategy, 'prepareNotFoundViewModel'],
            -90
        );
        $sharedEvents->attach(
            DispatchableInterface::class,
            MvcEvent::EVENT_DISPATCH,
            [$createViewModelListener, 'createViewModelFromNull'],
            -80
        );
        $sharedEvents->attach(
            DispatchableInterface::class,
            MvcEvent::EVENT_DISPATCH,
            [$injectTemplateListener, 'injectTemplate'],
            -90
        );
        $sharedEvents->attach(
            DispatchableInterface::class,
            MvcEvent::EVENT_DISPATCH,
            [$injectViewModelListener, 'injectViewModel'],
            -100
        );
    }

    /**
     * Retrieves the View instance
     *
     * @return View
     */
    public function getView()
    {
        if ($this->view) {
            return $this->view;
        }

        $this->view = $this->services->get(View::class);
        return $this->view;
    }

    /**
     * Configures the MvcEvent view model to ensure it has the template injected
     *
     * @return ModelInterface
     */
    public function getViewModel()
    {
        if ($this->viewModel) {
            return $this->viewModel;
        }

        $this->viewModel = $model = $this->event->getViewModel();
        $layoutTemplate  = $this->services->get('HttpDefaultRenderingStrategy')->getLayoutTemplate();
        $model->setTemplate($layoutTemplate);

        return $this->viewModel;
    }

    /**
     * Register additional mvc rendering strategies
     *
     * If there is a "mvc_strategies" key of the view manager configuration, loop
     * through it. Pull each as a service from the service manager, and, if it
     * is a ListenerAggregate, attach it to the view, at priority 100. This
     * latter allows each to trigger before the default mvc rendering strategy,
     * and for them to trigger in the order they are registered.
     *
     * @return void
     */
    protected function registerMvcRenderingStrategies(EventManagerInterface $events)
    {
        if (! isset($this->config['mvc_strategies'])) {
            return;
        }
        $mvcStrategies = $this->config['mvc_strategies'];
        if (is_string($mvcStrategies)) {
            $mvcStrategies = [$mvcStrategies];
        }
        if (! is_array($mvcStrategies) && ! $mvcStrategies instanceof Traversable) {
            return;
        }

        foreach ($mvcStrategies as $mvcStrategy) {
            if (! is_string($mvcStrategy)) {
                continue;
            }

            $listener = $this->services->get($mvcStrategy);
            if ($listener instanceof ListenerAggregateInterface) {
                $listener->attach($events, 100);
            }
        }
    }

    /**
     * Register additional view strategies
     *
     * If there is a "strategies" key of the view manager configuration, loop
     * through it. Pull each as a service from the service manager, and, if it
     * is a ListenerAggregate, attach it to the view, at priority 100. This
     * latter allows each to trigger before the default strategy, and for them
     * to trigger in the order they are registered.
     *
     * @return void
     */
    protected function registerViewStrategies()
    {
        if (! isset($this->config['strategies'])) {
            return;
        }
        $strategies = $this->config['strategies'];
        if (is_string($strategies)) {
            $strategies = [$strategies];
        }
        if (! is_array($strategies) && ! $strategies instanceof Traversable) {
            return;
        }

        $view   = $this->getView();
        $events = $view->getEventManager();

        foreach ($strategies as $strategy) {
            if (! is_string($strategy)) {
                continue;
            }

            $listener = $this->services->get($strategy);
            if ($listener instanceof ListenerAggregateInterface) {
                $listener->attach($events, 100);
            }
        }
    }

    /**
     * Injects the ViewModel view helper with the root view model.
     */
    private function injectViewModelIntoPlugin()
    {
        $model   = $this->getViewModel();
        $plugins = $this->services->get('ViewHelperManager');
        $plugin  = $plugins->get('viewmodel');
        $plugin->setRoot($model);
    }
}
