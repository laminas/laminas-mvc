<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\TestAsset;

use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\ServiceLocatorInterface;

use function call_user_func_array;

/**
 * Dummy locator used to test handling of locator objects by Application
 */
class Locator implements ServiceLocatorInterface
{
    protected array $services = [];

    /** @inheritDoc */
    public function get(string $id)
    {
        if (! isset($this->services[$id])) {
            throw new ServiceNotFoundException();
        }

        return call_user_func_array($this->services[$id]);
    }

    /** @inheritDoc */
    public function has($id)
    {
        return isset($this->services[$id]);
    }

    /** @inheritDoc */
    public function build($name, ?array $options = null)
    {
        if (! isset($this->services[$name])) {
            throw new ServiceNotFoundException();
        }

        return call_user_func_array($this->services[$name], $options);
    }

    public function add(string $name, mixed $callback): void
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
