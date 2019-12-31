<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Controller\Plugin;

use Laminas\Http\Response;
use Laminas\Mvc\Controller\Plugin\CreateHttpNotFoundModel;
use Laminas\View\Model\ViewModel;
use PHPUnit\Framework\TestCase;

/**
 * Tests for {@see \Laminas\Mvc\Controller\Plugin\CreateHttpNotFoundModel}
 *
 * @covers \Laminas\Mvc\Controller\Plugin\CreateHttpNotFoundModel
 */
class CreateHttpNotFoundModelTest extends TestCase
{
    public function testBuildsModelWithErrorMessageAndSetsResponseStatusCode()
    {
        $response = new Response();
        $plugin   = new CreateHttpNotFoundModel();

        $model    = $plugin->__invoke($response);

        $this->assertInstanceOf(ViewModel::class, $model);
        $this->assertSame('Page not found', $model->getVariable('content'));
        $this->assertSame(404, $response->getStatusCode());
    }
}
