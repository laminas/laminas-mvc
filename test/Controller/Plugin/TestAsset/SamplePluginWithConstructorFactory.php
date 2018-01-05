<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Mvc\Controller\Plugin\TestAsset;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class SamplePluginWithConstructorFactory implements FactoryInterface
{
    protected $options;

    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        return new SamplePluginWithConstructor($options);
    }

    public function setCreationOptions(array $options)
    {
        $this->options = $options;
    }
}
