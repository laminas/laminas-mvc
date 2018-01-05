<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Mvc\Controller\Plugin\TestAsset;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;

class SamplePluginWithConstructor extends AbstractPlugin
{
    protected $bar;

    public function __construct($bar = 'baz')
    {
        $this->bar = $bar;
    }

    public function getBar()
    {
        return $this->bar;
    }
}
