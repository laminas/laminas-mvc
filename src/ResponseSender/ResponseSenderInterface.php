<?php

namespace Laminas\Mvc\ResponseSender;

interface ResponseSenderInterface
{
    /**
     * Send the response
     *
     * @return void
     */
    public function __invoke(SendResponseEvent $event);
}
