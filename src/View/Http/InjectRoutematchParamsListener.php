<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Mvc\View\Http;

use Laminas\Console\Request as ConsoleRequest;
use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\ListenerAggregateInterface;
use Laminas\Http\Request as HttpRequest;
use Laminas\Mvc\MvcEvent;

/**
 * @category   Laminas
 * @package    Laminas_Mvc
 * @subpackage View
 */
class InjectRoutematchParamsListener implements ListenerAggregateInterface
{
    /**
     * @var \Laminas\Stdlib\CallbackHandler[]
     */
    protected $listeners = array();

    /**
     * Should request params overwrite existing request params?
     *
     * @var bool
     */
    protected $overwrite = true;

    /**
     * Attach the aggregate to the specified event manager
     *
     * @param  EventManagerInterface $events
     * @return void
     */
    public function attach(EventManagerInterface $events)
    {
        $this->listeners[] = $events->attach('dispatch', array($this, 'injectParams'), 90);
    }

    /**
     * Detach listeners
     *
     * @param  EventManagerInterface $events
     * @return void
     */
    public function detach(EventManagerInterface $events)
    {
        foreach ($this->listeners as $index => $listener) {
            if ($events->detach($listener)) {
                unset($this->listeners[$index]);
            }
        }
    }

    /**
     * Take parameters from RouteMatch and inject them into the request.
     *
     * @param  MvcEvent $e
     * @return void
     */
    public function injectParams(MvcEvent $e)
    {
        $routeMatchParams = $e->getRouteMatch()->getParams();
        $request = $e->getRequest();

        /** @var $params \Laminas\Stdlib\Parameters */
        if ($request instanceof ConsoleRequest) {
            $params = $request->params();
        } elseif ($request instanceof HttpRequest) {
            $params = $request->get();
        } else {
            // unsupported request type
            return;
        }

        if ($this->overwrite) {
            foreach ($routeMatchParams as $key => $val) {
                $params->$key = $val;
            }
        } else {
            foreach ($routeMatchParams as $key => $val) {
                if (!$params->offsetExists($key)) {
                    $params->$key = $val;
                }
            }
        }
    }

    /**
     * Should RouteMatch parameters replace existing Request params?
     *
     * @param  bool $overwrite
     */
    public function setOverwrite($overwrite)
    {
        $this->overwrite = $overwrite;
    }

    /**
     * @return bool
     */
    public function getOverwrite()
    {
        return $this->overwrite;
    }
}
