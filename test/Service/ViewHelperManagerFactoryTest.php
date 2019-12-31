<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Service;

use Laminas\Mvc\Service\ViewHelperManagerFactory;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit_Framework_TestCase as TestCase;

class ViewHelperManagerFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->services = new ServiceManager();
        $this->factory  = new ViewHelperManagerFactory();
    }

    /**
     * @return array
     */
    public function emptyConfiguration()
    {
        return array(
            'no-config'                => array(array()),
            'view-manager-config-only' => array(array('view_manager' => array())),
            'empty-doctype-config'     => array(array('view_manager' => array('doctype' => null))),
        );
    }

    /**
     * @dataProvider emptyConfiguration
     * @param  array $config
     * @return void
     */
    public function testDoctypeFactoryDoesNotRaiseErrorOnMissingConfiguration($config)
    {
        $this->services->setService('Config', $config);
        $manager = $this->factory->createService($this->services);
        $this->assertInstanceof('Laminas\View\HelperPluginManager', $manager);
        $doctype = $manager->get('doctype');
        $this->assertInstanceof('Laminas\View\Helper\Doctype', $doctype);
    }
}
