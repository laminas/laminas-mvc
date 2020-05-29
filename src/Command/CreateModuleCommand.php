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
use Laminas\Cli\Command\AbstractParamAwareCommand;
use Laminas\Cli\Input;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function copy;
use function file_get_contents;
use function file_put_contents;
use function is_dir;
use function is_file;

use const DIRECTORY_SEPARATOR;

final class CreateModuleCommand extends AbstractParamAwareCommand
{
    /** @var string */
    protected static $defaultName = 'mvc:module:create';

    protected function configure(): void
    {
        $this->setDescription('Creates new MVC Module');

        $this->addParam(
            (new Input\PathParam('dir', Input\PathParam::TYPE_DIR))
                ->setPathMustExist(true)
                ->setDescription('Directory with modules')
                ->setRequiredFlag(true)
        );

        $this->addParam(
            (new Input\StringParam('name'))
                ->setPattern('/^[A-Z][a-zA-Z0-9]*$/')
                ->setDescription('New module name')
                ->setRequiredFlag(true)
        );
    }

    /**
     * @param Input\ParamAwareInputInterface $input
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dir  = $input->getParam('dir');
        $name = $input->getParam('name');

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
