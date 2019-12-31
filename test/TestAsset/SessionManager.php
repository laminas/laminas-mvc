<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\TestAsset;

use Laminas\EventManager\EventManagerInterface;
use Laminas\Session\AbstractManager;

class SessionManager extends AbstractManager
{
    public $started = false;

    protected $configDefaultClass = 'Laminas\\Session\\Configuration\\StandardConfig';
    protected $storageDefaultClass = 'Laminas\\Session\\Storage\\ArrayStorage';

    public function start()
    {
        $this->started = true;
    }

    public function destroy()
    {
        $this->started = false;
    }

    public function stop()
    {
    }

    public function writeClose()
    {
        $this->started = false;
    }

    public function getName()
    {
    }

    public function setName($name)
    {
    }

    public function getId()
    {
    }

    public function setId($id)
    {
    }

    public function regenerateId()
    {
    }

    public function rememberMe($ttl = null)
    {
    }

    public function forgetMe()
    {
    }

    public function setValidatorChain(EventManagerInterface $chain)
    {
    }

    public function getValidatorChain()
    {
    }

    public function isValid()
    {
    }

    public function sessionExists()
    {
    }

    public function expireSessionCookie()
    {
    }
}
