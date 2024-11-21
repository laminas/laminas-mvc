<?php

namespace Laminas\Mvc\ResponseSender;

use Laminas\Http\Header\MultipleHeaderInterface;

use function header;
use function headers_sent;
use function is_iterable;

abstract class AbstractResponseSender implements ResponseSenderInterface
{
    /**
     * Send HTTP headers
     *
     * @return self
     */
    public function sendHeaders(SendResponseEvent $event)
    {
        if (headers_sent() || $event->headersSent()) {
            return $this;
        }

        $response = $event->getResponse();

        $headers = $response->getHeaders();

        if (is_iterable($headers)) {
            foreach ($response->getHeaders() as $header) {
                if ($header instanceof MultipleHeaderInterface) {
                    header($header->toString(), false);
                    continue;
                }
                header($header->toString());
            }
        }

        $status = $response->renderStatusLine();
        header((string) $status);

        $event->setHeadersSent();
        return $this;
    }
}
