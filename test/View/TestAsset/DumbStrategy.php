<?php

namespace LaminasTest\Mvc\View\TestAsset;

use ArrayObject;
use Laminas\View\Model\ModelInterface as Model;
use Laminas\View\Renderer\RendererInterface as Renderer;
use Laminas\View\Resolver\ResolverInterface as Resolver;

/**
 * Mock renderer
 */
class DumbStrategy implements Renderer
{
    protected $resolver;

    public function getEngine()
    {
        return $this;
    }

    public function setResolver(Resolver $resolver)
    {
        $this->resolver = $resolver;
    }

    public function render($nameOrModel, $values = null)
    {
        $options = [];
        $values  = (array) $values;
        if ($nameOrModel instanceof Model) {
            $options   = $nameOrModel->getOptions();
            $variables = $nameOrModel->getVariables();
            if ($variables instanceof ArrayObject) {
                $variables = $variables->getArrayCopy();
            }
            $values = array_merge($variables, $values);
            if (array_key_exists('template', $options)) {
                $nameOrModel = $options['template'];
            } else {
                $nameOrModel = '[UNKNOWN]';
            }
        }

        return sprintf('%s (%s): %s', $nameOrModel, json_encode($options), json_encode($values));
    }
}
