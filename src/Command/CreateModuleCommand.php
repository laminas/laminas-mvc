<?php

declare(strict_types=1);

namespace Laminas\Mvc\Command;

use DirectoryIterator;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

use function copy;
use function file_get_contents;
use function file_put_contents;
use function getcwd;
use function is_dir;
use function is_file;
use function is_writable;
use function preg_match;

use const DIRECTORY_SEPARATOR;

final class CreateModuleCommand extends Command
{
    protected static $defaultName = 'mvc:module:create';

    protected function configure(): void
    {
        $this->setDescription('Creates new MVC Module');

        $this->addOption(
            'dir',
            null,
            InputOption::VALUE_OPTIONAL,
            'Directory with modules'
        );

        $this->addOption(
            'name',
            null,
            InputOption::VALUE_OPTIONAL,
            'New module name'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dir = $this->getModuleDir($input, $output);
        $input->setOption('dir', $dir);

        $name = $this->getModuleName($input, $output);
        $input->setOption('name', $name);

        $path = $dir . DIRECTORY_SEPARATOR . $name;
        if (is_dir($path)) {
            throw new InvalidArgumentException("Module {$name} already exists at {$dir}");
        }

        $this->copy(
            __DIR__ . '/../../template/Module',
            $path,
            static function (string $content) use ($name) : string {
                return strtr($content, [
                    '%name%' => $name,
                ]);
            }
        );

        $output->writeln("Module {$name} has been created.");

        return 0;
    }

    private function getModuleName(InputInterface $input, OutputInterface $output) : string
    {
        $validator = static function (string $answer) : string {
            if (! preg_match('/^[A-Z][a-zA-Z0-9]*$/', $answer)) {
                throw new InvalidArgumentException('Invalid module name: ' . $answer);
            }

            return $answer;
        };

        $name = $input->getOption('name');
        if ($name !== null) {
            return $validator($name);
        }

        $question = new Question('Please module name: ');
        $question->setValidator($validator);

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        return $helper->ask($input, $output, $question);
    }

    private function getModuleDir(InputInterface $input, OutputInterface $output): string
    {
        $validator = static function (string $answer) : string {
            if (! is_dir($answer)) {
                throw new InvalidArgumentException("Path {$answer} is not a valid directory.");
            }

            if (! is_writable($answer)) {
                throw new InvalidArgumentException("Module directory {$answer} is not writable.");
            }

            return $answer;
        };

        $default = getcwd() . '/module';
        $dir = $input->getOption('dir');

        if ($dir === null && ! $input->isInteractive()) {
            $dir = $default;
        }

        if ($dir !== null) {
            return $validator($dir);
        }

        $callback = static function (string $userInput) : array {
            // Strip any characters from the last slash to the end of the string
            // to keep only the last directory and generate suggestions for it
            $inputPath = preg_replace('%(/|^)[^/]*$%', '$1', $userInput);
            $inputPath = '' === $inputPath ? '.' : $inputPath;

            // CAUTION - this example code allows unrestricted access to the
            // entire filesystem. In real applications, restrict the directories
            // where files and dirs can be found
            $foundFilesAndDirs = is_dir($inputPath) ? scandir($inputPath) : [];

            return array_map(static function ($dirOrFile) use ($inputPath) {
                return $inputPath . $dirOrFile;
            }, $foundFilesAndDirs);
        };

        $question = new Question('Please provide modules directory: ', $default);
        $question->setAutocompleterCallback($callback);
        $question->setValidator($validator);

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        return $helper->ask($input, $output, $question);
    }

    private function copy(string $source, string $dest, callable $contentCallback)
    {
        if (is_file($source)) {
            copy($source, $dest);
            $content = $contentCallback(file_get_contents($dest));
            file_put_contents($dest, $content);

            return;
        }

        mkdir($dest, 0755);
        foreach (new DirectoryIterator($source) as $fileInfo) {
            if ($fileInfo->isDot()) {
                continue;
            }

            $this->copy(
                $fileInfo->getPathname(),
                $dest . DIRECTORY_SEPARATOR . $fileInfo->getFilename(),
                $contentCallback
            );
        }
    }
}
