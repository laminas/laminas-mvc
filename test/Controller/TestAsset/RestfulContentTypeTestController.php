<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Controller\TestAsset;

use Laminas\Mvc\Controller\AbstractRestfulController;

class RestfulContentTypeTestController extends AbstractRestfulController
{
    /**
     * Update an existing resource
     */
    public function update(mixed $id, mixed $data): array
    {
        return [
            'id'   => $id,
            'data' => $data,
        ];
    }
}
