<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc\Service;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\Mvc\Exception;
use Zend\Mvc\Service\DiAbstractServiceFactoryFactory;
use Zend\ServiceManager\ServiceManager;

class DiAbstractServiceFactoryFactoryTest extends TestCase
{
    public function testUsingFactoryWithServiceManagerV3RaisesExceptionPromptingForNewRequirements()
    {
        $container = new ServiceManager();

        if (! method_exists($container, 'configure')) {
            $this->markTestSkipped('Test is only relevant for zend-servicemanager v3');
        }

        $factory = new DiAbstractServiceFactoryFactory();
        $this->setExpectedException(Exception\RuntimeException::class, 'zend-servicemanager-di');
        $factory($container, DiAbstractServiceFactoryFactory::class);
    }
}
