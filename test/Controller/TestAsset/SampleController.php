<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Controller\TestAsset;

use Laminas\Mvc\Controller\AbstractActionController;

class SampleController extends AbstractActionController implements SampleInterface
{
    /**
     * @return mixed
     */
    public function testAction()
    {
        return ['content' => 'test'];
    }

    /**
     * @return mixed
     */
    public function testSomeStrangelySeparatedWordsAction()
    {
        return ['content' => 'Test Some Strangely Separated Words'];
    }

    /**
     * @return mixed
     */
    public function testCircularAction()
    {
        return $this->forward()->dispatch('sample', ['action' => 'test-circular']);
    }
}
