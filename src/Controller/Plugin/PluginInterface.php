<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Mvc\Controller\Plugin;

use Zend\Stdlib\DispatchableInterface as Dispatchable;

interface PluginInterface
{
    /**
     * Set the current controller instance
     *
     * @param  Dispatchable $controller
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
