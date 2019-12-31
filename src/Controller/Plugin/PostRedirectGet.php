<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Mvc\Controller\Plugin;

use Laminas\Mvc\Exception\RuntimeException;
use Laminas\Session\Container;

/**
 * Plugin to help facilitate Post/Redirect/Get (http://en.wikipedia.org/wiki/Post/Redirect/Get)
 *
 * @category Laminas
 * @package Laminas_Mvc
 * @subpackage Controller
 */
class PostRedirectGet extends AbstractPlugin
{
    public function __invoke($redirect = null, $redirectToUrl = false)
    {
        $controller = $this->getController();
        $request    = $controller->getRequest();
        $params     = array();

        if (null === $redirect) {
            $routeMatch = $controller->getEvent()->getRouteMatch();

            $redirect = $routeMatch->getMatchedRouteName();
            $params   = $routeMatch->getParams();
        }

        $container = new Container('prg_post1');

        if ($request->isPost()) {
            $container->setExpirationHops(1, 'post');
            $container->post = $request->getPost()->toArray();

            if (method_exists($controller, 'getPluginManager')) {
                // get the redirect plugin from the plugin manager
                $redirector = $controller->getPluginManager()->get('Redirect');
            } else {
                /*
                 * If the user wants to redirect to a route, the redirector has to come
                 * from the plugin manager -- otherwise no router will be injected
                 */
                if ($redirectToUrl === false) {
                    throw new RuntimeException('Could not redirect to a route without a router');
                }

                $redirector = new Redirect();
            }

            if ($redirectToUrl === false) {
                $response = $redirector->toRoute($redirect, $params);
                $response->setStatusCode(303);
                return $response;
            }

            $response = $redirector->toUrl($redirect);
            $response->setStatusCode(303);

            return $response;
        } else {
            if ($container->post !== null) {
                $post = $container->post;
                unset($container->post);
                return $post;
            }

            return false;
        }
    }
}

