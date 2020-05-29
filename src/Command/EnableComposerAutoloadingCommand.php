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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function file_get_contents;
use function file_put_contents;
use function json_decode;
use function json_encode;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

final class EnableComposerAutoloadingCommand extends AbstractParamAwareCommand
{
    /** @var string */
    protected static $defaultName = 'mvc:module:enable-autoloading';

    protected function configure(): void
    {
        $this->setName(self::$defaultName);
        $this->setDescription('Enables PSR-4 autoloading for module');

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

        $composerFile = getcwd() . '/composer.json';
        $composer     = json_decode(file_get_contents($composerFile), true);

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
