<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Laminas\Mvc\Command;

use Laminas\ComponentInstaller\Collection;
use Laminas\ComponentInstaller\ConfigDiscovery;
use Laminas\ComponentInstaller\ConfigOption;
use Laminas\ComponentInstaller\Injector\DevelopmentConfigInjector;
use Laminas\ComponentInstaller\Injector\DevelopmentWorkConfigInjector;
use Laminas\ComponentInstaller\Injector\InjectorInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class RegisterModuleCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'mvc:module:register';

    protected function configure(): void
    {
        $this->setName(self::$defaultName);
        $this->setDescription('Register given module within the application');
        $this->addOption(
            'module',
            null,
            InputOption::VALUE_OPTIONAL,
            'Module name to register'
        );
        $this->addOption(
            'dir',
            null,
            InputOption::VALUE_OPTIONAL,
            'Directory with modules'
        );
        $this->addOption(
            'mode',
            null,
            InputOption::VALUE_OPTIONAL,
            'Production or development mode'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $module = $input->getOption('module');
        $dir = $input->getOption('dir');
        $mode = $input->getOption('mode');

        $configDiscovery = new ConfigDiscovery();
        $configOptions = $configDiscovery->getAvailableConfigOptions(new Collection([InjectorInterface::TYPE_MODULE]));

        $configOptions->each(static function (ConfigOption $configOption) use ($mode, $module) {
            $injector = $configOption->getInjector();

            if ($injector instanceof DevelopmentConfigInjector
                || $injector instanceof DevelopmentWorkConfigInjector
            ) {
                if ($mode === 'Development') {
                    $injector->inject($module, InjectorInterface::TYPE_MODULE);
                }
            } elseif ($mode !== 'Development') {
                $injector->inject($module, InjectorInterface::TYPE_MODULE);
            }
        });

        return 0;
    }
}
