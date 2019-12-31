<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Router\Http\TestAsset;

use Laminas\Mvc\Router\Http\RouteInterface;
use Laminas\Mvc\Router\Http\RouteMatch;
use Laminas\Stdlib\RequestInterface;

/**
 * Dummy route.
 *
 * @category   Laminas
 * @package    Laminas_Mvc_Router
 * @subpackage UnitTests
 */
class DummyRoute implements RouteInterface
{
    /**
     * match(): defined by RouteInterface interface.
     *
     * @see    Route::match()
     * @param  RequestInterface $request
     * @param  integer $pathOffset
     * @return RouteMatch
     */
    public function match(RequestInterface $request, $pathOffset = null)
    {
        return new RouteMatch(array('offset' => $pathOffset), -4);
    }

    /**
     * assemble(): defined by RouteInterface interface.
     *
     * @see    Route::assemble()
     * @param  array $params
     * @param  array $options
     * @return mixed
     */
    public function assemble(array $params = null, array $options = null)
    {
        return '';
    }

    /**
     * factory(): defined by RouteInterface interface
     *
     * @param  array|Traversable $options
     * @return DummyRoute
     */
    public static function factory($options = array())
    {
        return new static();
    }

    /**
     * getAssembledParams(): defined by RouteInterface interface.
     *
     * @see    Route::getAssembledParams
     * @return array
     */
    public function getAssembledParams()
    {
        return array();
    }
}
