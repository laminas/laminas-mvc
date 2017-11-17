# Using middleware within event listeners

Within the MVC workflow, you can use middleware within event listeners by
converting the request and response objects composed in the event to PSR-7
equivalents using [zend-psr7bridge](https://github.com/zendframework/zend-psr7bridge).

As an example, consider the following `AuthorizationMiddleware`:

```php
namespace Application\Middleware;

use Psr\Http\Message\ServerRequestInterface as RequestInterface;
use Psr\Http\Message\ResponseInterface;

class AuthorizationMiddleware
{
    public function __invoke(RequestInterface $request, ResponseInterface $response, callable $next = null)
    {
        // handle authorization here...
    }
}
```

Since the request and response composed in `MvcEvent` instances are specifically
from zend-http, we will use zend-psr7bridge to convert them to PSR-7
equivalents. As an example, consider the following module declaration, which
registers a `dispatch` listener to invoke the above middleware:

```php
namespace Application;

use Psr\Http\Message\ResponseInterface;
use Zend\Psr7Bridge\Psr7ServerRequest;
use Zend\Psr7Bridge\Psr7Response;

class Module
{
    public function onBootstrap($e)
    {
        $app          = $e->getApplication();
        $eventManager = $app->getEventManager();
        $services     = $app->getServiceManager();

        $eventManager->attach($e::EVENT_DISPATCH, function ($e) use ($services) {
            $request  = Psr7ServerRequest::fromZend($e->getRequest());
            $response = Psr7Response::fromZend($e->getResponse());
            $done     = function ($request, $response) {
            };

            $result   = ($services->get(Middleware\AuthorizationMiddleware::class))(
                $request,
                $response,
                $done
            );

            if ($result) {
                return Psr7Response::toZend($result);
            }
        }, 2);
    }
}
```
