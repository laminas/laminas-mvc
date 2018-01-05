<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Mvc\TestAsset;

use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\Exception\ServiceNotFoundException;

/**
 * Dummy locator used to test handling of locator objects by Application
 */
class Locator implements ServiceLocatorInterface
{
    protected $services = [];

    public function get($name)
    {
        if (! isset($this->services[$name])) {
            throw new ServiceNotFoundException();
        }

        return call_user_func_array($this->services[$name]);
    }

    public function has($name)
    {
        return (isset($this->services[$name]));
    }

    public function build($name, array $options = null)
    {
        if (! isset($this->services[$name])) {
            throw new ServiceNotFoundException();
        }

        return call_user_func_array($this->services[$name], $options);
    }

    public function add($name, $callback)
    {
        $this->services[$name] = $callback;
    }

    public function remove($name)
    {
        if (isset($this->services[$name])) {
            unset($this->services[$name]);
        }
    }
}
