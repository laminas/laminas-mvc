<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\ResponseSender\TestAsset;

use Laminas\Console\Response;
use Laminas\Mvc\ResponseSender\ConsoleResponseSender as BaseConsoleResponseSender;
use Laminas\Mvc\ResponseSender\SendResponseEvent;

class ConsoleResponseSender extends BaseConsoleResponseSender
{
    /**
     * Send the response
     *
     * This method is overridden, it's purpose is to disable the exit call and instead
     * just return the error level for unit testing
     *
     * @param SendResponseEvent $event
     * @return int
     */
    public function __invoke(SendResponseEvent $event)
    {
        $response = $event->getResponse();
        if ($response instanceof Response) {
            $this->sendContent($event);
            $errorLevel = (int) $response->getMetadata('errorLevel',0);
            $event->stopPropagation(true);
            return $errorLevel;
        }
    }
}
