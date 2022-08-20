<?php

declare(strict_types=1);

namespace Laminas\Mvc\Controller\Plugin;

use Laminas\Stdlib\DispatchableInterface as Dispatchable;

interface PluginInterface
{
    /**
     * Set the current controller instance
     *
     * @return void
     */
    public function setController(Dispatchable $controller);

    /**
     * Get the current controller instance
     *
     * @return null|Dispatchable
     */
    public function getController();
}
