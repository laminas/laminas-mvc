<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Laminas\Mvc\Command;

use Laminas\Cli\Input\InputParam;
use Laminas\Cli\Input\InputParamTrait;
use Laminas\ComponentInstaller\Collection;
use Laminas\ComponentInstaller\ConfigDiscovery;
use Laminas\ComponentInstaller\ConfigOption;
use Laminas\ComponentInstaller\Injector\DevelopmentConfigInjector;
use Laminas\ComponentInstaller\Injector\DevelopmentWorkConfigInjector;
use Laminas\ComponentInstaller\Injector\InjectorInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class RegisterModuleCommand extends Command
{
    use InputParamTrait;

    /** @var string */
    protected static $defaultName = 'mvc:module:register';

    protected function configure(): void
    {
        $this->setName(self::$defaultName);
        $this->setDescription('Register given module within the application');

        $this->addParam(
            'module',
            'Module name to enable',
            InputParam::TYPE_STRING,
            true,
            null,
            [
                'pattern' => '/^[A-Z][a-zA-Z0-9]*$/',
            ]
        );
        $this->addParam(
            'dir',
            'Directory with modules',
            InputParam::TYPE_PATH,
            true,
            'module',
            [
                'type' => 'dir',
                'existing' => true,
            ]
        );
        $this->addParam(
            'mode',
            'Where the module will be used',
            InputParam::TYPE_CHOICE,
            true,
            null,
            [
                'haystack' => [
                    'Production',
                    'Development',
                ],
            ]
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $module = $this->getParam('module');
        $dir = $this->getParam('dir');
        $mode = $this->getParam('mode');

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

        $output->writeln(sprintf(
            '<comment>Module %s has been registered in application</comment>',
            $module
        ));

        return 0;
    }
}
