<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Mvc\Service;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;
use Zend\View\Renderer\PhpRenderer;

class ViewPhpRendererFactory implements FactoryInterface
{
    /**
     * @param  ContainerInterface $container
     * @param  string $name
     * @param  null|array $options
     * @return PhpRenderer
     */
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        $renderer = new PhpRenderer();
        $renderer->setHelperPluginManager($container->get('ViewHelperManager'));
        $renderer->setResolver($container->get('ViewResolver'));

        return $renderer;
    }
}
