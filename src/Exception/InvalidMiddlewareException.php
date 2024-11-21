<?php

namespace Laminas\Mvc\Exception;

use function sprintf;

/**
 * @deprecated Since 3.2.0
 */
class InvalidMiddlewareException extends RuntimeException
{
    private ?string $middlewareName = null;

    /**
     * @param string $middlewareName
     * @return self
     */
    public static function fromMiddlewareName($middlewareName)
    {
        $middlewareName           = (string) $middlewareName;
        $instance                 = new self(sprintf('Cannot dispatch middleware %s', $middlewareName));
        $instance->middlewareName = $middlewareName;
        return $instance;
    }

    /**
     * @return self
     */
    public static function fromNull()
    {
        return new self('Middleware name cannot be null');
    }

    /**
     * @return string
     */
    public function toMiddlewareName()
    {
        return $this->middlewareName ?? '';
    }
}
