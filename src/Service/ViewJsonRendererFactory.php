<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Mvc\Service;

use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Renderer\JsonRenderer;

class ViewJsonRendererFactory implements FactoryInterface
{
    /**
     * Create and return the JSON view renderer
     *
     * @param  ServiceLocatorInterface $serviceLocator
     * @return JsonRenderer
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $jsonRenderer = new JsonRenderer();
        return $jsonRenderer;
    }
}
