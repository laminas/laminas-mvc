<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\ResponseSender;

use Laminas\Http\Response;
use Laminas\Mvc\ResponseSender\SendResponseEvent;
use Laminas\Mvc\ResponseSender\SimpleStreamResponseSender;
use Laminas\Stdlib;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

use function file_get_contents;
use function fopen;
use function ob_get_clean;
use function ob_start;

class SimpleStreamResponseSenderTest extends TestCase
{
    public function testSendResponseIgnoresInvalidResponseTypes(): void
    {
        $mockResponse          = $this->getMockForAbstractClass(Stdlib\ResponseInterface::class);
        $mockSendResponseEvent = $this->getSendResponseEventMock($mockResponse);
        $responseSender        = new SimpleStreamResponseSender();
        ob_start();
        $responseSender($mockSendResponseEvent);
        $body = ob_get_clean();
        $this->assertEquals('', $body);
    }

    public function testSendResponseTwoTimesPrintsResponseOnlyOnce(): void
    {
        $file         = fopen(__DIR__ . '/TestAsset/sample-stream-file.txt', 'rb');
        $mockResponse = $this->createMock(Response\Stream::class);
        $mockResponse->expects($this->once())->method('getStream')->willReturn($file);
        $mockSendResponseEvent = $this->getSendResponseEventMock($mockResponse);
        $responseSender        = new SimpleStreamResponseSender();
        ob_start();
        $responseSender($mockSendResponseEvent);
        $body     = ob_get_clean();
        $expected = file_get_contents(__DIR__ . '/TestAsset/sample-stream-file.txt');
        $this->assertEquals($expected, $body);

        ob_start();
        $responseSender($mockSendResponseEvent);
        $body = ob_get_clean();
        $this->assertEquals('', $body);
    }

    /**
     * @return SendResponseEvent&MockObject
     */
    protected function getSendResponseEventMock(Stdlib\ResponseInterface $response): MockObject
    {
        $mockSendResponseEvent = $this->getMockBuilder(SendResponseEvent::class)
            ->onlyMethods(['getResponse'])
            ->getMock();
        $mockSendResponseEvent->method('getResponse')->willReturn($response);
        return $mockSendResponseEvent;
    }
}
