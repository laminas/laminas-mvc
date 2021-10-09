<?php

namespace Laminas\Mvc\Exception;

/**
 * @deprecated Since 3.2.0
 */
class InvalidMiddlewareException extends RuntimeException
{
    /**
     * @var string
     */
    private $middlewareName;

    /**
     * @param string $middlewareName
     * @return self
     */
    public static function fromMiddlewareName($middlewareName)
    {
        $middlewareName = (string)$middlewareName;
        $instance = new self(sprintf('Cannot dispatch middleware %s', $middlewareName));
        $instance->middlewareName = $middlewareName;
        return $instance;
    }

    public static function fromNull()
    {
        $instance = new self('Middleware name cannot be null');
        return $instance;
    }

    /**
     * @return string
     */
    public function toMiddlewareName()
    {
        return null !== $this->middlewareName ? $this->middlewareName : '';
    }
}
