# The SendResponse Event

laminas-mvc defines and utilizes a custom `Laminas\EventManager\Event` for updating
the response object prior to emitting it, `Laminas\Mvc\ResponseSender\SendResponseEvent`.
The event allows listeners to set response headers and content.

The methods it defines are:

- `setResponse($response)`
- `getResponse()`
- `setContentSent()`
- `contentSent()`
- `setHeadersSent()`
- `headersSent()`

## Listeners

Currently, three listeners are listening to this event at different priorities based on which
listener is used most.

Class                                                        | Priority | Method Called | Description
------------------------------------------------------------ | -------: | ------------- | -----------
`Laminas\Mvc\SendResponseListener\PhpEnvironmentResponseSender` | -1000    | `__invoke`    | This is used in HTTP contexts (this is the most often used).
`Laminas\Mvc\SendResponseListener\ConsoleResponseSender`        | -2000    | `__invoke`    | This is used in console contexts.
`Laminas\Mvc\SendResponseListener\SimpleStreamResponseSender`   | -3000    | `__invoke`    |

Because each listener has negative priority, adding your own logic to modify the
`Response` involves adding a new listener without priority (as priority defaults
to 1); thus, your own listener will execute before any of the defaults.

## Triggered By

This event is executed when the `MvcEvent::FINISH` event is triggered, with a priority of -10000.
