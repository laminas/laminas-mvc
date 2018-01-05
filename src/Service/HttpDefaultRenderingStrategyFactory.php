<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Mvc\Service;

use Interop\Container\ContainerInterface;
use Zend\Mvc\View\Http\DefaultRenderingStrategy;
use Zend\ServiceManager\Factory\FactoryInterface;
use Zend\View\View;

class HttpDefaultRenderingStrategyFactory implements FactoryInterface
{
    use HttpViewManagerConfigTrait;

    /**
     * @param  ContainerInterface $container
     * @param  string $name
     * @param  null|array $options
     * @return DefaultRenderingStrategy
     */
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        $strategy = new DefaultRenderingStrategy($container->get(View::class));
        $config   = $this->getConfig($container);

        $this->injectLayoutTemplate($strategy, $config);

        return $strategy;
    }

    /**
     * Inject layout template.
     *
     * Uses layout template from configuration; if none available, defaults to "layout/layout".
     *
     * @param DefaultRenderingStrategy $strategy
     * @param array $config
     */
    private function injectLayoutTemplate(DefaultRenderingStrategy $strategy, array $config)
    {
        $layout = isset($config['layout']) ? $config['layout'] : 'layout/layout';
        $strategy->setLayoutTemplate($layout);
    }
}
