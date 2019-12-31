<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Controller\TestAsset;

use Laminas\Mvc\Controller\AbstractController;
use Laminas\Mvc\MvcEvent;

class AbstractControllerStub extends AbstractController
{
    public function onDispatch(MvcEvent $e)
    {
        // noop
    }
}
