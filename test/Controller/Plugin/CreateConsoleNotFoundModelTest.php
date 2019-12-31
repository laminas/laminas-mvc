<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Controller\Plugin;

use Laminas\Mvc\Controller\Plugin\CreateConsoleNotFoundModel;
use PHPUnit_framework_TestCase as TestCase;

/**
 * Tests for {@see \Laminas\Mvc\Controller\Plugin\CreateConsoleNotFoundModel}
 *
 * @covers \Laminas\Mvc\Controller\Plugin\CreateConsoleNotFoundModel
 */
class CreateConsoleNotFoundModelTest extends TestCase
{
    public function testCanReturnModelWithErrorMessageAndErrorLevel()
    {
        $plugin = new CreateConsoleNotFoundModel();

        $model = $plugin->__invoke();

        $this->assertInstanceOf('Laminas\\View\\Model\\ConsoleModel', $model);
        $this->assertSame('Page not found', $model->getResult());
        $this->assertSame(1, $model->getErrorLevel());
    }
}
