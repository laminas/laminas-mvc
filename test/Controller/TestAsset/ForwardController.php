<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Controller\TestAsset;

use Laminas\Mvc\Controller\AbstractActionController;

class ForwardController extends AbstractActionController
{
    public function testAction(): array
    {
        return ['content' => __METHOD__];
    }

    public function testMatchesAction(): mixed
    {
        $e = $this->getEvent();
        return $e->getRouteMatch()->getParams();
    }

    public function notFoundAction(): array
    {
        return [
            'status' => 'not-found',
            'params' => $this->params()->fromRoute(),
        ];
    }
}
