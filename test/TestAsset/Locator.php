<?php

namespace LaminasTest\Mvc\TestAsset;

use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\ServiceLocatorInterface;

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
