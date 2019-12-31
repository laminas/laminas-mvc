<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Controller\TestAsset;

use Laminas\ServiceManager\AbstractFactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class ControllerLoaderAbstractFactory implements AbstractFactoryInterface
{
    protected $classmap = array(
        'path' => 'LaminasTest\Mvc\TestAsset\PathController',
    );

    public function canCreateServiceWithName(ServiceLocatorInterface $sl, $cName, $rName)
    {
        $classname = $this->classmap[$cName];
        return class_exists($classname);
    }

    public function createServiceWithName(ServiceLocatorInterface $sl, $cName, $rName)
    {
        $classname = $this->classmap[$cName];
        $controller = new $classname;
        return $controller;
    }
}
