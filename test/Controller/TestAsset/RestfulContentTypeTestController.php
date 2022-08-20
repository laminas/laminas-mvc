<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Controller\TestAsset;

use Laminas\Mvc\Controller\AbstractRestfulController;

class RestfulContentTypeTestController extends AbstractRestfulController
{
    /**
     * Update an existing resource
     *
     * @param  mixed $id
     * @param  mixed $data
     * @return array
     */
    public function update($id, $data)
    {
        return [
            'id'   => $id,
            'data' => $data,
        ];
    }
}
