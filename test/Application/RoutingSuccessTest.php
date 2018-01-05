<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Mvc\Application;

use PHPUnit\Framework\TestCase;
use Zend\Mvc\MvcEvent;
use Zend\Router\RouteMatch;

class RoutingSuccessTest extends TestCase
{
    use PathControllerTrait;

    public function testRoutingIsExcecutedDuringRun()
    {
        $application = $this->prepareApplication();

        $log = [];

        $application->getEventManager()->attach(MvcEvent::EVENT_ROUTE, function ($e) use (&$log) {
            $match = $e->getRouteMatch();
            $this->assertInstanceOf(RouteMatch::class, $match, 'Did not receive expected route match');
            $log['route-match'] = $match;
        }, -100);

        $application->run();
        $this->assertArrayHasKey('route-match', $log);
        $this->assertInstanceOf(RouteMatch::class, $log['route-match']);
    }
}
