<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Laminas\Mvc\Command;

use DirectoryIterator;
use InvalidArgumentException;
use Laminas\Cli\Input\InputParam;
use Laminas\Cli\Input\InputParamTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function copy;
use function file_get_contents;
use function file_put_contents;
use function is_dir;
use function is_file;

use const DIRECTORY_SEPARATOR;

final class CreateModuleCommand extends Command
{
    use InputParamTrait;

    /** @var string */
    protected static $defaultName = 'mvc:module:create';

    protected function configure(): void
    {
        $this->setDescription('Creates new MVC Module');

        $this->addParam(
            'dir',
            'Directory with modules',
            InputParam::TYPE_PATH,
            true,
            'module',
            [
                'type' => 'dir',
                'existing' => true,
                // 'writable' => true, // @todo not supported yet
            ]
        );

        $this->addParam(
            'name',
            'New module name',
            InputParam::TYPE_STRING,
            true,
            null,
            [
                'pattern' => '/^[A-Z][a-zA-Z0-9]*$/',
            ]
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dir = $this->getParam('dir');
        $name = $this->getParam('name');

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

        $output->writeln("<comment>Module {$name} has been created.</comment>");

        return 0;
    }

    private function copy(string $source, string $dest, callable $contentCallback): void
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
