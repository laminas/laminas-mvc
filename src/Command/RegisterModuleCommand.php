<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Laminas\Mvc\Command;

use Laminas\Cli\Command\AbstractParamAwareCommand;
use Laminas\Cli\Input;
use Laminas\ComponentInstaller\Collection;
use Laminas\ComponentInstaller\ConfigDiscovery;
use Laminas\ComponentInstaller\ConfigOption;
use Laminas\ComponentInstaller\Injector\DevelopmentConfigInjector;
use Laminas\ComponentInstaller\Injector\DevelopmentWorkConfigInjector;
use Laminas\ComponentInstaller\Injector\InjectorInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class RegisterModuleCommand extends AbstractParamAwareCommand
{
    /** @var string */
    protected static $defaultName = 'mvc:module:register';

    protected function configure(): void
    {
        $this->setName(self::$defaultName);
        $this->setDescription('Register given module within the application');

        $this->addParam(
            (new Input\StringParam('module'))
                ->setPattern('/^[A-Z][a-zA-Z0-9]*$/')
                ->setDescription('Module name to enable')
                ->setRequiredFlag(true)
        );
        $this->addParam(
            (new Input\PathParam('dir', Input\PathParam::TYPE_DIR))
                ->setPathMustExist(true)
                ->setDescription('Directory with modules')
                ->setRequiredFlag(true)
                ->setDescription('module')
        );
        $this->addParam(
            (new Input\ChoiceParam('mode', [
                'Production',
                'Development',
            ]))
                ->setDescription('Where the module will be used')
                ->setRequiredFlag(true)
        );
    }

    /**
     * @param Input\ParamAwareInputInterface $input
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $module = $input->getParam('module');
        $dir    = $input->getParam('dir');
        $mode   = $input->getParam('mode');

        $configDiscovery = new ConfigDiscovery();
        $configOptions   = $configDiscovery->getAvailableConfigOptions(new Collection([InjectorInterface::TYPE_MODULE]));

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
