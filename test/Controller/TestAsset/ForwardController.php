<?php

namespace LaminasTest\Mvc\Controller\TestAsset;

use Laminas\Mvc\Controller\AbstractActionController;

class ForwardController extends AbstractActionController
{
    public function testAction()
    {
        return ['content' => __METHOD__];
    }

    public function testMatchesAction()
    {
        $e = $this->getEvent();
        return $e->getRouteMatch()->getParams();
    }

    public function notFoundAction()
    {
        return [
            'status' => 'not-found',
            'params' => $this->params()->fromRoute(),
        ];
    }
}
