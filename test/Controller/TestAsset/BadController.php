<?php

namespace LaminasTest\Mvc\Controller\TestAsset;

use Laminas\Mvc\Controller\AbstractActionController;

class BadController extends AbstractActionController
{
    public function testAction()
    {
        throw new \Exception('Raised an exception');
    }

    public function testPhp7ErrorAction()
    {
        throw new \Error('Raised an error');
    }
}
