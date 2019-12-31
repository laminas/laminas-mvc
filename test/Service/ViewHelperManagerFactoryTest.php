<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Service;

use Laminas\Console\Request as ConsoleRequest;
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
        return [
            'no-config'                => [[]],
            'view-manager-config-only' => [['view_manager' => []]],
            'empty-doctype-config'     => [['view_manager' => ['doctype' => null]]],
        ];
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

    public function testConsoleRequestsResultInSilentFailure()
    {
        $this->services->setService('Config', []);
        $this->services->setService('Request', new ConsoleRequest());

        $manager = $this->factory->createService($this->services);

        $doctype = $manager->get('doctype');
        $this->assertInstanceof('Laminas\View\Helper\Doctype', $doctype);

        $basePath = $manager->get('basepath');
        $this->assertInstanceof('Laminas\View\Helper\BasePath', $basePath);
    }

    /**
     * @group 6247
     */
    public function testConsoleRequestWithBasePathConsole()
    {
        $this->services->setService('Config',
            [
                'view_manager' => [
                    'base_path_console' => 'http://test.com'
                ]
            ]
        );
        $this->services->setService('Request', new ConsoleRequest());

        $manager = $this->factory->createService($this->services);

        $basePath = $manager->get('basepath');
        $this->assertEquals('http://test.com', $basePath());
    }
}
