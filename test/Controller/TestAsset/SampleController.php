<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Mvc\Controller\TestAsset;

use Zend\Mvc\Controller\AbstractActionController;

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
