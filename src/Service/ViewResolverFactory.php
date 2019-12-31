<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Mvc\Service;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Resolver as ViewResolver;

class ViewResolverFactory implements FactoryInterface
{
    /**
     * Create the aggregate view resolver
     *
     * Creates a Laminas\View\Resolver\AggregateResolver and attaches the template
     * map resolver and path stack resolver
     *
     * @param  ContainerInterface $container
     * @param  string $name
     * @param  null|array $options
     * @return ViewResolver\AggregateResolver
     */
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        $resolver = new ViewResolver\AggregateResolver();

        /* @var $mapResolver \Laminas\View\Resolver\ResolverInterface */
        $mapResolver             = $container->get('ViewTemplateMapResolver');
        /* @var $pathResolver \Laminas\View\Resolver\ResolverInterface */
        $pathResolver            = $container->get('ViewTemplatePathStack');
        /* @var $prefixPathStackResolver \Laminas\View\Resolver\ResolverInterface */
        $prefixPathStackResolver = $container->get('ViewPrefixPathStackResolver');

        $resolver
            ->attach($mapResolver)
            ->attach($pathResolver)
            ->attach($prefixPathStackResolver)
            ->attach(new ViewResolver\RelativeFallbackResolver($mapResolver))
            ->attach(new ViewResolver\RelativeFallbackResolver($pathResolver))
            ->attach(new ViewResolver\RelativeFallbackResolver($prefixPathStackResolver));

        return $resolver;
    }

    /**
     * Create and return ViewResolver\AggregateResolver instance
     *
     * For use with laminas-servicemanager v2; proxies to __invoke().
     *
     * @param ServiceLocatorInterface $container
     * @return ViewResolver\AggregateResolver
     */
    public function createService(ServiceLocatorInterface $container)
    {
        return $this($container, ViewResolver\AggregateResolver::class);
    }
}
