<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
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
