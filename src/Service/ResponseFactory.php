<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Mvc\Service;

use Interop\Container\ContainerInterface;
use Zend\Http\PhpEnvironment\Response as HttpResponse;
use Zend\ServiceManager\Factory\FactoryInterface;

class ResponseFactory implements FactoryInterface
{
    /**
     * Create and return a response instance.
     *
     * @param  ContainerInterface $container
     * @param  string $name
     * @param  null|array $options
     * @return HttpResponse
     */
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        return new HttpResponse();
    }
}
