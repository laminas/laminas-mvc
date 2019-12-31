<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Mvc\Service;

use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Resolver as ViewResolver;

/**
 * @category   Laminas
 * @package    Laminas_Mvc
 * @subpackage Service
 */
class ViewResolverFactory implements FactoryInterface
{
    /**
     * Create the aggregate view resolver
     *
     * Creates a Laminas\View\Resolver\AggregateResolver and attaches the template
     * map resolver and path stack resolver
     *
     * @param  ServiceLocatorInterface $serviceLocator
     * @return ViewResolver\AggregateResolver
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $resolver = new ViewResolver\AggregateResolver();
        $resolver->attach($serviceLocator->get('ViewTemplateMapResolver'));
        $resolver->attach($serviceLocator->get('ViewTemplatePathStack'));
        return $resolver;
    }
}
