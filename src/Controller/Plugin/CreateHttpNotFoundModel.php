<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Mvc\Controller\Plugin;

use Zend\Http\Response;
use Zend\View\Model\ViewModel;

class CreateHttpNotFoundModel extends AbstractPlugin
{
    /**
     * Create an HTTP view model representing a "not found" page
     *
     * @param  Response $response
     *
     * @return ViewModel
     */
    public function __invoke(Response $response)
    {
        $response->setStatusCode(404);

        return new ViewModel(['content' => 'Page not found']);
    }
}
