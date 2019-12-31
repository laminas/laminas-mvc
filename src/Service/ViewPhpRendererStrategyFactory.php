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
use Laminas\View\Renderer\PhpRenderer;
use Laminas\View\Strategy\PhpRendererStrategy;

class ViewPhpRendererStrategyFactory implements FactoryInterface
{
    /**
     * @param  ContainerInterface $container
     * @param  string $name
     * @param  null|array $options
     * @return PhpRendererStrategy
     */
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        return new PhpRendererStrategy($container->get(PhpRenderer::class));
    }

    /**
     * Create and return PhpRendererStrategy instance
     *
     * For use with laminas-servicemanager v2; proxies to __invoke().
     *
     * @param ServiceLocatorInterface $container
     * @return PhpRendererStrategy
     */
    public function createService(ServiceLocatorInterface $container)
    {
        return $this($container, PhpRendererStrategy::class);
    }
}
