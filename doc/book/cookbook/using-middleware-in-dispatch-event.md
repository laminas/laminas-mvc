# Using Middleware in "dispatch" Event

During Mvc Workflow, you can use the Middleware in 'dispatch' event by provide `Psr7Bridge`. For example, you have `AuthorizationMiddleware`:

```php
namespace Application\Middleware;

class AuthorizationMiddleware
{
    public function __invoke($request, $response, $next = null)
    {
        // handle authorization here...
    }
}
```

As the request and response in 'dispatch' event is a `Zend\Http` Request and Response object, we need the bridge to convert into PSR-7 Request and Response. To do that, you can do the following:

```php
namespace Application;

use Application\Middleware\AuthorizationMiddleware;
use Zend\Psr7Bridge\Psr7ServerRequest;
use Zend\Psr7Bridge\Psr7Response;

class Module
{
    public function onBootstrap($e)
    {
        $app          = $e->getApplication();
        $eventManager = $app->getEventManager();
        $services     = $app->getServiceManager();

        $eventManager->attach('dispatch', function ($e) use ($services) {
            $request  = Psr7ServerRequest::fromZend($e->getRequest());
            $response = Psr7Response::fromZend($e->getResponse());
            $result   = ($services->get(AuthorizationMiddleware::class))($request, $response);
        }, 2);
    }
}
```
