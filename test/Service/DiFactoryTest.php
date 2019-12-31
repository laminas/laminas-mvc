<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Service;

use Laminas\Mvc\Service\DiFactory;
use Laminas\ServiceManager\ServiceManager;

class DiFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testWillInitializeDiAndDiAbstractFactory()
    {
        $serviceManager = new ServiceManager();
        $serviceManager->setService('config', ['di' => ['']]);
        $serviceManager->setFactory('Di', new DiFactory());

        $di = $serviceManager->get('Di');
        $this->assertInstanceOf('Laminas\Di\Di', $di);
    }
}
