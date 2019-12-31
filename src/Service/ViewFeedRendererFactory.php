<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Mvc\Service;

use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Renderer\FeedRenderer;

class ViewFeedRendererFactory implements FactoryInterface
{
    /**
     * Create and return the feed view renderer
     *
     * @param  ServiceLocatorInterface $serviceLocator
     * @return FeedRenderer
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $feedRenderer = new FeedRenderer();
        return $feedRenderer;
    }
}
