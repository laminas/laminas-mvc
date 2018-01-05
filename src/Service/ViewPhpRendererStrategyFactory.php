<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Mvc\Service;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;
use Zend\View\Renderer\PhpRenderer;
use Zend\View\Strategy\PhpRendererStrategy;

class ViewPhpRendererStrategyFactory implements FactoryInterface
{
    /**
     * @param  ContainerInterface $container
     * @param  string $name
     * @param  null|array $options
     * @return PhpRendererStrategy
     */
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        return new PhpRendererStrategy($container->get(PhpRenderer::class));
    }
}
