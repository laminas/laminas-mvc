<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Mvc\Service;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;
use Zend\View\Resolver\PrefixPathStackResolver;

class ViewPrefixPathStackResolverFactory implements FactoryInterface
{
    /**
     * Create the template prefix view resolver
     *
     * Creates a Zend\View\Resolver\PrefixPathStackResolver and populates it with the
     * ['view_manager']['prefix_template_path_stack']
     *
     * @param  ContainerInterface $container
     * @param  string $name
     * @param  null|array $options
     * @return PrefixPathStackResolver
     */
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        $config   = $container->get('config');
        $prefixes = [];

        if (isset($config['view_manager']['prefix_template_path_stack'])) {
            $prefixes = $config['view_manager']['prefix_template_path_stack'];
        }

        return new PrefixPathStackResolver($prefixes);
    }
}
