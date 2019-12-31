<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Controller\Plugin;

use Laminas\Authentication\AuthenticationService;
use Laminas\Authentication\Storage\NonPersistent as NonPersistentStorage;
use Laminas\Mvc\Controller\Plugin\Identity as IdentityPlugin;

/**
 * Tests Identity plugin
 *
 * @category   Laminas
 * @package    Laminas_Mvc
 * @subpackage UnitTests
 */
class IdentityTest extends \PHPUnit_Framework_TestCase
{
    public function testGetIdentity()
    {
        $identity = new TestAsset\IdentityObject();
        $identity->setUsername('a username');
        $identity->setPassword('a password');

        $authenticationService = new AuthenticationService(new NonPersistentStorage, new TestAsset\AuthenticationAdapter);

        $identityPlugin = new IdentityPlugin;
        $identityPlugin->setAuthenticationService($authenticationService);

        $this->assertNull($identityPlugin());

        $this->assertFalse($authenticationService->hasIdentity());

        $authenticationService->getAdapter()->setIdentity($identity);
        $result = $authenticationService->authenticate();
        $this->assertTrue($result->isValid());

        $this->assertEquals($identity, $identityPlugin());
    }
}
