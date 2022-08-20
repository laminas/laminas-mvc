<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Controller\TestAsset;

use Laminas\Mvc\Controller\AbstractActionController;

class ForwardController extends AbstractActionController
{
    /**
     * @return mixed
     */
    public function testAction()
    {
        return ['content' => __METHOD__];
    }

    /**
     * @return mixed
     */
    public function testMatchesAction()
    {
        $e = $this->getEvent();
        return $e->getRouteMatch()->getParams();
    }

    /**
     * @return mixed
     */
    public function notFoundAction()
    {
        return [
            'status' => 'not-found',
            'params' => $this->params()->fromRoute(),
        ];
    }
}
