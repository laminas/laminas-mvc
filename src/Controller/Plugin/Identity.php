<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Mvc\Controller\Plugin;

use Laminas\Authentication\AuthenticationService;
use Laminas\Mvc\Exception;

/**
 * Controller plugin to fetch the authenticated identity.
 */
class Identity extends AbstractPlugin
{
    /**
     * @var AuthenticationService
     */
    protected $authenticationService;

    /**
     * @return AuthenticationService
     */
    public function getAuthenticationService()
    {
        return $this->authenticationService;
    }

    /**
     * @param AuthenticationService $authenticationService
     */
    public function setAuthenticationService(AuthenticationService $authenticationService)
    {
        $this->authenticationService = $authenticationService;
    }

    /**
     * Retrieve the current identity, if any.
     *
     * If none is present, returns null.
     *
     * @return mixed|null
     * @throws Exception\RuntimeException
     */
    public function __invoke()
    {
        if (!$this->authenticationService instanceof AuthenticationService) {
            throw new Exception\RuntimeException('No AuthenticationService instance provided');
        }
        if (!$this->authenticationService->hasIdentity()) {
            return null;
        }
        return $this->authenticationService->getIdentity();
    }
}
