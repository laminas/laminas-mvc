# Quick Start

In this example, `/hello/world?message=welcome` will display a page containing the message provided through the URL.
This requires several steps:

- Create a controller to obtain the message from the URL, and pass it as a variable to the view
- Create a view to display a page containing the message
- Create a route to match the URL to the controller

Throughout this example, we will work in the `Application` module of the skeleton. The relevant files are located under `module/Application`.

## Install the Laminas MVC Skeleton Application

To get started, install the skeleton application via Composer.

If you have not yet done so, [install Composer](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx).
Once you have, use the `create-project` command to create a new application:

```bash
$ composer create-project -s dev laminas/laminas-mvc-skeleton my-application
```

## Create a Controller

Laminas MVC has several base controller classes for you to start with:

- `AbstractActionController` matches routes to methods within this class.
  For example, if you had a route with the "list" action, the "listAction" method will be called.

- `AbstractRestfulController` determines the HTTP method from the request, and calls a method according to that.
  For example, a `POST` HTTP method will call the `update()` method in the class.

- You can also create custom controllers by implementing `Laminas\Stdlib\DispatchableInterface`.

Learn more about controllers [in the chapter on controllers](controllers.md).

We will use the `AbstractActionController` base controller.
Create the file `src/Application/Controller/HelloController.php`.
Add the following code:

```php
<?php
namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

// Create an action controller.
class HelloController extends AbstractActionController
{
    // Define an action "world".
    public function worldAction()
    {
        // Get "message" from the query parameters.
        // In production code, it's a good idea to sanitize user input.
        $message = $this->params()->fromQuery('message', 'hello');

        // Pass variables to the view.
        return new ViewModel(['message' => $message]);
    }
}
```

By default, this controller will render the view script located in `view/application/hello/world.phtml`.
You can customize this behavior.
Learn more about views [in the laminas-view documentation](https://docs.laminas.dev/laminas-view/quick-start/).

## Create a View Script

Create the file `view/application/hello/world.phtml` and add the following code:

```php
<h1>Greetings!</h1>

<p>You said "<?php echo $this->escapeHtml($message) ?>".</p>
```

INFO: **Escaping output**
The method `escapeHtml()` is a [view helper](https://docs.laminas.dev/laminas-view/helpers/intro/), and it's designed to help mitigate XSS attacks.
Never trust user input.
If you are at all uncertain about the source of a variable in your view, escape it using one of the view helpers, depending on the type of data.

## Create a Route

Routes determine which controller to call based on the URI and other information from the request.

A couple of array values are declared for routes and controllers are added to  `module/Application/config/module.config.php` as shown below:

```php
<?php

declare(strict_types=1);

// Declaring our module's namespace here.  Allows us to automatically reference classes in the 
// src folder of our module with pathnames relative to the src folder. 
namespace Application;

// Explicitly referencing these classes permits us to reference them by their names below. 
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\Factory\InvokableFactory;

return [
    'router' => [
        'routes' => [
            'home' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'application' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/application[/:action]',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            // New Route: used to generate links, among other things.
            'hello-world' => [
                'type' => Literal::class, // exact match of URI path
                'options' => [
                    'route' => '/hello/world', // URI path
                    'defaults' => [
                        'controller' => Controller\HelloController::class, // unique name
                        'action'     => 'world',
                    ],
                ],
            ],
            // end of New route insertion
        ],
    ],
    'controllers' => [
        // Tell the application how to instantiate our controller class
        'factories' => [
            Controller\IndexController::class => InvokableFactory::class,
            // Add the HelloController class to the array of invokable controllers. 
            Controller\HelloController::class => InvokableFactory::class,

        ],
    ],
    'view_manager' => [
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_map' => [
            'layout/layout'           => __DIR__ . '/../view/layout/layout.phtml',
            'application/index/index' => __DIR__ . '/../view/application/index/index.phtml',
            'error/404'               => __DIR__ . '/../view/error/404.phtml',
            'error/index'             => __DIR__ . '/../view/error/index.phtml',
        ],
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
];
```

When the URI path of the request matches `/hello/world`, the specified controller and action will be used.
The controller name must be present under the `controllers` key in the configuration.
The associated class will then be instantiated and invoked.

Learn more about routing [in the laminas-router documentation](https://docs.laminas.dev/laminas-router/routing).

## Test it Out!

Create a new vhost pointing its document root to the `public` directory of your application, and fire it up in a browser.
You should see the default homepage template of [laminas-mvc-skeleton](https://github.com/laminas/laminas-mvc-skeleton).

Append the path `/hello/world` to your URL and load the page.
You should now get the following content:

```html
<h1>Greetings!</h1>

<p>You said "hello".</p>
```

Append `?message=welcome` to your URL.
You should now get:

```html
<h1>Greetings!</h1>

<p>You said "welcome".</p>
```

Congratulations!
You've created your first Laminas MVC controller!

## Learn More

- [Creating custom modules](https://docs.laminas.dev/tutorials/getting-started/modules/)
- [Controllers](controllers.md)
- [Views](https://docs.laminas.dev/laminas-view/quick-start)
- [Routing](https://docs.laminas.dev/laminas-router/routing)
