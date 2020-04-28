<?php

declare(strict_types=1);

namespace Laminas\Mvc\Command;

use Laminas\Cli\CommandListenerInterface;
use Laminas\ComponentInstaller\Collection;
use Laminas\ComponentInstaller\ConfigDiscovery;
use Laminas\ComponentInstaller\ConfigOption;
use Laminas\ComponentInstaller\Injector\DevelopmentConfigInjector;
use Laminas\ComponentInstaller\Injector\DevelopmentWorkConfigInjector;
use Laminas\ComponentInstaller\Injector\InjectorInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleEvent;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

final class RegisterModuleCommand extends Command implements CommandListenerInterface
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

    public function __invoke(ConsoleEvent $event) : int
    {
        $input = $event->getInput();
        $output = $event->getOutput();

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $question = new ConfirmationQuestion(sprintf(
            PHP_EOL . 'Do you want to register the %s module within the application? [Y/n] ',
            $input->getOption('module')
        ));

        if ($helper->ask($input, $output, $question)) {
            $params = [
                'command' => self::$defaultName,
                '--module' => $input->getOption('module'),
                '--dir' => $input->getOption('dir'),
                '--mode' => $input->getOption('mode'),
            ];

            return $this->getApplication()->run(new ArrayInput($params), $output);
        }

        return 0;
    }
}
