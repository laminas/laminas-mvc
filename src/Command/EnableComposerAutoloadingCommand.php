<?php

declare(strict_types=1);

namespace Laminas\Mvc\Command;

use Laminas\Cli\CommandListenerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleEvent;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;

use function file_get_contents;
use function file_put_contents;
use function json_decode;
use function json_encode;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

final class EnableComposerAutoloadingCommand extends Command implements CommandListenerInterface
{
    /** @var string */
    protected static $defaultName = 'mvc:module:enable-autoloading';

    protected function configure(): void
    {
        $this->setName(self::$defaultName);
        $this->setDescription('Enables PSR-4 autoloading for module');
        $this->addOption(
            'module',
            null,
            InputOption::VALUE_OPTIONAL,
            'Module name to enable'
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
        if ($mode === null) {
            /** @var QuestionHelper $helper */
            $helper = $this->getHelper('question');

            $question = new ChoiceQuestion(
                'Do you want register module as production or development? [Production/Development]',
                ['Production', 'Development'],
                'Production'
            );

            $mode = $helper->ask($input, $output, $question);
            $input->setOption('mode', $module);
        }

        $composerFile = getcwd() . '/composer.json';
        $composer = json_decode(file_get_contents($composerFile), true);

        $autoloadSection = $mode === 'Development'
            ? 'autoload-dev'
            : 'autoload';

        $composer[$autoloadSection]['psr-4'][$module . '\\'] = sprintf('%s/%s/src/', $dir, $module);
        $composer['autoload-dev']['psr-4'][$module . 'Test\\'] = sprintf('%s/%s/test/', $dir, $module);

        file_put_contents(
            $composerFile,
            json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES, JSON_UNESCAPED_UNICODE)
        );

        system('composer dump-autoload');

        return 0;
    }

    public function __invoke(ConsoleEvent $event) : int
    {
        $input = $event->getInput();
        $output = $event->getOutput();

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $question = new ConfirmationQuestion(sprintf(
            PHP_EOL . 'Do you want to enable composer PSR-4 autoloading for %s module? [Y/n] ',
            $input->getOption('name')
        ));

        if ($helper->ask($input, $output, $question)) {
            $params = [
                'command' => self::$defaultName,
                '--module' => $input->getOption('name'),
                '--dir' => $input->getOption('dir'),
            ];

            return $this->getApplication()->run(new ArrayInput($params), $output);
        }

        return 0;
    }
}
