<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Controller\TestAsset;

use Laminas\Mvc\Controller\AbstractActionController;

class SampleController extends AbstractActionController implements SampleInterface
{
    public function testAction()
    {
        return ['content' => 'test'];
    }

    public function testSomeStrangelySeparatedWordsAction()
    {
        return ['content' => 'Test Some Strangely Separated Words'];
    }

    public function testCircularAction()
    {
        return $this->forward()->dispatch('sample', ['action' => 'test-circular']);
    }
}
