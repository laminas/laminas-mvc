<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Mvc\Controller;

use Laminas\Console\Adapter\AdapterInterface as ConsoleAdaper;
use Laminas\Console\Request as ConsoleRequest;
use Laminas\Mvc\Exception\InvalidArgumentException;
use Laminas\Stdlib\RequestInterface;
use Laminas\Stdlib\ResponseInterface;

class AbstractConsoleController extends AbstractActionController
{
    /**
     * @var ConsoleAdaper
     */
    protected $console;

    /**
     * @param ConsoleAdaper $console
     * @return self
     */
    public function setConsole(ConsoleAdaper $console)
    {
        $this->console = $console;

        return $this;
    }

    /**
     * @return ConsoleAdaper
     */
    public function getConsole()
    {
        return $this->console;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch(RequestInterface $request, ResponseInterface $response = null)
    {
        if (! $request instanceof ConsoleRequest) {
            throw new InvalidArgumentException(sprintf(
                '%s can only dispatch requests in a console environment',
                get_called_class()
            ));
        }
        return parent::dispatch($request, $response);
    }
}
