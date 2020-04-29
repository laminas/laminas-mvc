<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Laminas\Mvc\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

use function file_get_contents;
use function file_put_contents;
use function json_decode;
use function json_encode;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

final class EnableComposerAutoloadingCommand extends Command
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

        $output->writeln(sprintf(
            '<comment>PSR-4 autoloading has been enabled for %s module.</comment> Running `composer dump-autoload`...',
            $module
        ));

        system('composer dump-autoload');

        return 0;
    }
}
