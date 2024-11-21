<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Controller\TestAsset;

use Laminas\Mvc\Controller\AbstractActionController;

class SampleController extends AbstractActionController implements SampleInterface
{
    public function testAction(): array
    {
        return ['content' => 'test'];
    }

    public function testSomeStrangelySeparatedWordsAction(): array
    {
        return ['content' => 'Test Some Strangely Separated Words'];
    }

    public function testCircularAction(): mixed
    {
        return $this->forward()->dispatch('sample', ['action' => 'test-circular']);
    }
}
