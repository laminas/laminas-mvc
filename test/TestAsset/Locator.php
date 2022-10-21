<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\TestAsset;

use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\ServiceLocatorInterface;

/**
 * Dummy locator used to test handling of locator objects by Application
 */
class Locator implements ServiceLocatorInterface
{
    protected array $services = [];

    /**
     * @param string $name
     * @return mixed
     */
    public function get($name)
    {
        if (! isset($this->services[$name])) {
            throw new ServiceNotFoundException();
        }

        return $this->services[$name]();
    }

    /**
     * @param string $name
     * @return bool
     */
    public function has($name)
    {
        return isset($this->services[$name]);
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function build($name, ?array $options = null)
    {
        if (! isset($this->services[$name])) {
            throw new ServiceNotFoundException();
        }

        return $this->services[$name]($options);
    }

    public function add(string $name, callable $callback): void
    {
        $this->services[$name] = $callback;
    }

    public function remove(string $name): void
    {
        if (isset($this->services[$name])) {
            unset($this->services[$name]);
        }
    }
}
