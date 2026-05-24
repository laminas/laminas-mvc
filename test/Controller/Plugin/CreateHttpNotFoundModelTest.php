<?php

declare(strict_types=1);

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
    public function testBuildsModelWithErrorMessageAndSetsResponseStatusCode(): void
    {
        $response = new Response();
        $plugin   = new CreateHttpNotFoundModel();

        $model = $plugin->__invoke($response);

        self::assertInstanceOf(ViewModel::class, $model);
        self::assertSame('Page not found', $model->getVariable('content'));
        self::assertSame(404, $response->getStatusCode());
    }
}
