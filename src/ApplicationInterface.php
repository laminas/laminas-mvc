<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Mvc;

use Laminas\EventManager\EventsCapableInterface;

/**
 * @category   Laminas
 * @package    Laminas_Mvc
 */
interface ApplicationInterface extends EventsCapableInterface
{
    /**
     * Get the locator object
     *
     * @return \Laminas\ServiceManager\ServiceLocatorInterface
     */
    public function getServiceManager();

    /**
     * Get the request object
     *
     * @return Request
     */
    public function getRequest();

    /**
     * Get the response object
     *
     * @return Response
     */
    public function getResponse();

    /**
     * Run the application
     *
     * @return \Laminas\Http\Response
     */
    public function run();
}
