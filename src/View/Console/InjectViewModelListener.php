<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Mvc\View\Console;

use Laminas\EventManager\EventManagerInterface as Events;
use Laminas\EventManager\ListenerAggregateInterface;
use Laminas\Mvc\MvcEvent;
use Laminas\Mvc\Router\RouteMatch;
use Laminas\Mvc\View\Http\InjectViewModelListener as HttpInjectViewModelListener;
use Laminas\View\Model\ModelInterface as ViewModel;

class InjectViewModelListener extends HttpInjectViewModelListener implements ListenerAggregateInterface
{}
