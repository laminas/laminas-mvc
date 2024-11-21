<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Service\TestAsset;

use DomainException;

class InvalidDispatchableClass
{
    public function __construct()
    {
        throw new DomainException('Should not instantiate this!');
    }
}
