<?php

declare(strict_types=1);

namespace Laminas\Mvc;

use Laminas\EventManager\EventManager;
use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\SharedEventManager;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\ModuleManager\Listener\ServiceListener;
use Laminas\ModuleManager\ModuleManager;
use Laminas\Mvc\Service\EventManagerFactory;
use Laminas\Mvc\Service\ModuleManagerFactory;
use Laminas\Mvc\Service\ServiceListenerFactory;

class ConfigProvider
{
    public function __invoke() : array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    public function getDependencies() : array
    {
        return [
            'aliases' => [
                'EventManagerInterface' => EventManager::class,
                EventManagerInterface::class => 'EventManager',
                ModuleManager::class => 'ModuleManager',
                ServiceListener::class => 'ServiceListener',
                SharedEventManager::class => 'SharedEventManager',
                'SharedEventManagerInterface' => 'SharedEventManager',
                SharedEventManagerInterface::class => 'SharedEventManager',
            ],
            'factories' => [
                'EventManager' => EventManagerFactory::class,
                'ModuleManager' => ModuleManagerFactory::class,
                'ServiceListener' => ServiceListenerFactory::class,
                'SharedEventManager' => static function () {
                    return new SharedEventManager();
                },

            ],
            'shared' => [
                'EventManager' => false,
            ],
        ];
    }
}
