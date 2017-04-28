<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Mvc\Exception;

class ReachedFinalHandlerException extends RuntimeException
{
    /**
     * @return self
     */
    public static function create()
    {
        return new self('Reached the final handler for middleware pipe - check the pipe configuration');
    }
}
