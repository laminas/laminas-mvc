<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

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
                    'mvc:module:register'           => Command\RegisterModuleCommand::class,
                    'mvc:module:enable-autoloading' => Command\EnableComposerAutoloadingCommand::class,
                    'mvc:module:create'             => Command\CreateModuleCommand::class,
                ],
                'chains' => [
                    Command\CreateModuleCommand::class => [
                        Command\EnableComposerAutoloadingCommand::class => [
                            '--name' => '--module',
                            '--dir'  => '--dir',
                        ],
                    ],
                    Command\EnableComposerAutoloadingCommand::class => [
                        Command\RegisterModuleCommand::class => [
                            '--module' => '--module',
                            '--dir'    => '--dir',
                            '--mode'   => '--mode',
                        ],
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
