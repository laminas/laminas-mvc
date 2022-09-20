<?php

declare(strict_types=1);

namespace Laminas\Mvc\Service;

use Laminas\Mvc\View\Http\ViewManager as HttpViewManager;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class ViewManagerFactory implements FactoryInterface
{
    /**
     * Create and return a view manager.
     *
     * @param  string $name
     * @param  null|array $options
     * @return HttpViewManager
     */
    public function __invoke(ContainerInterface $container, $name, ?array $options = null)
    {
        return $container->get('HttpViewManager');
    }
}
