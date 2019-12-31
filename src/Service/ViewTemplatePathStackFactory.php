<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Mvc\Service;

use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Resolver as ViewResolver;

/**
 * @category   Laminas
 * @package    Laminas_Mvc
 * @subpackage Service
 */
class ViewTemplatePathStackFactory implements FactoryInterface
{
    /**
     * Create the template map view resolver
     *
     * Creates a Laminas\View\Resolver\TemplatePathStack and populates it with the
     * ['view_manager']['template_path_stack']
     *
     * @param  ServiceLocatorInterface $serviceLocator
     * @return ViewResolver\TemplatePathStack
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Config');
        $stack = array();
        if (is_array($config) && isset($config['view_manager'])) {
            $config = $config['view_manager'];
            if (is_array($config) && isset($config['template_path_stack'])) {
                $stack = $config['template_path_stack'];
            }
        }

        $templatePathStack = new ViewResolver\TemplatePathStack();
        $templatePathStack->addPaths($stack);
        return $templatePathStack;
    }
}
