<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Mvc\Controller;

use Laminas\Mvc\Exception;
use Laminas\Mvc\MvcEvent;
use Laminas\View\Model\ViewModel;

/**
 * Basic action controller
 *
 * @category   Laminas
 * @package    Laminas_Mvc
 * @subpackage Controller
 */
abstract class AbstractActionController extends AbstractController
{
    /**
     * @var string
     */
    protected $eventIdentifier = __CLASS__;

    /**
     * Default action if none provided
     *
     * @return array
     */
    public function indexAction()
    {
        return new ViewModel(array(
            'content' => 'Placeholder page'
        ));
    }

    /**
     * Action called if matched action does not exist
     *
     * @return array
     */
    public function notFoundAction()
    {
        $response   = $this->response;
        $event      = $this->getEvent();
        $routeMatch = $event->getRouteMatch();

        $response->setStatusCode(404);
        $routeMatch->setParam('action', 'not-found');

        return new ViewModel(array(
            'content' => 'Page not found'
        ));
    }

    /**
     * Execute the request
     *
     * @param  MvcEvent $e
     * @return mixed
     * @throws Exception\DomainException
     */
    public function onDispatch(MvcEvent $e)
    {
        $routeMatch = $e->getRouteMatch();
        if (!$routeMatch) {
            /**
             * @todo Determine requirements for when route match is missing.
             *       Potentially allow pulling directly from request metadata?
             */
            throw new Exception\DomainException('Missing route matches; unsure how to retrieve action');
        }

        $action = $routeMatch->getParam('action', 'not-found');
        $method = static::getMethodFromAction($action);

        if (!method_exists($this, $method)) {
            $method = 'notFoundAction';
        }

        $actionResponse = $this->$method();

        $e->setResult($actionResponse);

        return $actionResponse;
    }
}
