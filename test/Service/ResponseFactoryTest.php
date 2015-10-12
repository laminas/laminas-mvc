<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc\Service;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\Console\Response as ConsoleResponse;
use Zend\Http\Response as HttpResponse;
use Zend\Mvc\Service\ResponseFactory;

class ResponseFactoryTest extends TestCase
{
    use FactoryEnvironmentTrait;

    public function tearDown()
    {
        $this->setConsoleEnvironment(true);
    }

    public function testFactoryCreatesConsoleResponseInConsoleEnvironment()
    {
        $this->setConsoleEnvironment(true);
        $factory = new ResponseFactory();
        $response = $factory($this->createContainer(), 'Response');
        $this->assertInstanceOf(ConsoleResponse::class, $response);
    }

    public function testFactoryCreatesHttpResponseInNonConsoleEnvironment()
    {
        $this->setConsoleEnvironment(false);
        $factory = new ResponseFactory();
        $response = $factory($this->createContainer(), 'Response');
        $this->assertInstanceOf(HttpResponse::class, $response);
    }
}
