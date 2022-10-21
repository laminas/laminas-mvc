<?php

declare(strict_types=1);

namespace Laminas\Mvc\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\View\Renderer\PhpRenderer;
use Laminas\View\Strategy\PhpRendererStrategy;
use Psr\Container\ContainerInterface;

class ViewPhpRendererStrategyFactory implements FactoryInterface
{
    /**
     * @param  string $name
     * @param  null|array $options
     * @return PhpRendererStrategy
     */
    public function __invoke(ContainerInterface $container, $name, ?array $options = null)
    {
        return new PhpRendererStrategy($container->get(PhpRenderer::class));
    }
}
