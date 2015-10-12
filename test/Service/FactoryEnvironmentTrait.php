<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc\Service;

use Interop\Container\ContainerInterface;
use ReflectionProperty;
use Zend\Console\Console;

trait FactoryEnvironmentTrait
{
    private function setConsoleEnvironment($isConsole = true)
    {
        $r = new ReflectionProperty(Console::class, 'isConsole');
        $r->setAccessible(true);
        $r->setValue((bool) $isConsole);
    }

    private function createContainer()
    {
        return $this->prophesize(ContainerInterface::class)->reveal();
    }
}
