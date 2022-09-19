<?php

declare(strict_types=1);

namespace Laminas\Mvc\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\View\Strategy\JsonStrategy;
use Psr\Container\ContainerInterface;

class ViewJsonStrategyFactory implements FactoryInterface
{
    /**
     * Create and return the JSON view strategy
     *
     * Retrieves the ViewJsonRenderer service from the service locator, and
     * injects it into the constructor for the JSON strategy.
     *
     * It then attaches the strategy to the View service, at a priority of 100.
     *
     * @param  string $name
     * @param  null|array $options
     * @return JsonStrategy
     */
    public function __invoke(ContainerInterface $container, $name, ?array $options = null)
    {
        $jsonRenderer = $container->get('ViewJsonRenderer');
        return new JsonStrategy($jsonRenderer);
    }
}
