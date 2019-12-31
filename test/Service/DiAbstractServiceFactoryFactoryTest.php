<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Service;

use Laminas\Mvc\Exception;
use Laminas\Mvc\Service\DiAbstractServiceFactoryFactory;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit_Framework_TestCase as TestCase;

class DiAbstractServiceFactoryFactoryTest extends TestCase
{
    public function testUsingFactoryWithServiceManagerV3RaisesExceptionPromptingForNewRequirements()
    {
        $container = new ServiceManager();

        if (! method_exists($container, 'configure')) {
            $this->markTestSkipped('Test is only relevant for laminas-servicemanager v3');
        }

        $factory = new DiAbstractServiceFactoryFactory();
        $this->setExpectedException(Exception\RuntimeException::class, 'laminas-servicemanager-di');
        $factory($container, DiAbstractServiceFactoryFactory::class);
    }
}
