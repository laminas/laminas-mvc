<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc\Controller\TestAsset;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZendTest\Mvc\TestAsset\PathController;

class ControllerLoaderAbstractFactory implements AbstractFactoryInterface
{
    protected $classmap = [
        'path' => PathController::class,
    ];

    public function canCreate(ContainerInterface $container, $name)
    {
        if (! isset($this->classmap[$name])) {
            return false;
        }

        $classname = $this->classmap[$name];
        return class_exists($classname);
    }

    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        $classname = $this->classmap[$name];
        return new $classname;
    }
}
