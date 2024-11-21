<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Controller\TestAsset;

use Error;
use Exception;
use Laminas\Mvc\Controller\AbstractActionController;

class BadController extends AbstractActionController
{
    public function testAction(): void
    {
        throw new Exception('Raised an exception');
    }

    public function testPhp7ErrorAction(): void
    {
        throw new Error('Raised an error');
    }
}
