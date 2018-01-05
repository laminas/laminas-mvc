<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Mvc\Exception;

final class InvalidMiddlewareException extends RuntimeException
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
