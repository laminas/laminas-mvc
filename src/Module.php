<?php

declare(strict_types=1);

namespace Laminas\Mvc;

use Laminas\ServiceManager\Factory\InvokableFactory;

class Module
{
    public function getConfig() : array
    {
        return [
            'laminas-cli' => [
                'commands' => [
                    'mvc:module:register' => Command\RegisterModuleCommand::class,
                    'mvc:module:enable-autoloading' => Command\EnableComposerAutoloadingCommand::class,
                    'mvc:module:create' => Command\CreateModuleCommand::class,
                ],
                'listeners' => [
                    Command\CreateModuleCommand::class => [
                        Command\EnableComposerAutoloadingCommand::class,
                    ],
                    Command\EnableComposerAutoloadingCommand::class => [
                        Command\RegisterModuleCommand::class,
                    ],
                ],
            ],
            'service_manager' => [
                'factories' => [
                    Command\RegisterModuleCommand::class            => InvokableFactory::class,
                    Command\EnableComposerAutoloadingCommand::class => InvokableFactory::class,
                    Command\CreateModuleCommand::class              => Command\CreateModuleCommand::class,
                ],
            ],
        ];
    }
}
