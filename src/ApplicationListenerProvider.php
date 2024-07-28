<?php

declare(strict_types=1);

namespace Laminas\Mvc;

use Laminas\EventManager\ListenerAggregateInterface;
use Laminas\Mvc\Exception\DomainException;
use Psr\Container\ContainerInterface;

use function array_merge;
use function array_unique;
use function get_debug_type;
use function is_string;
use function sprintf;

use const SORT_REGULAR;

/**
 * Double-dispatch listener provider for Application to ensure default listeners and to ensure listeners attached
 * once on Application instantiation.
 *
 * Delays fetching listener aggregates from container until attempt to attach them is made.
 */
final class ApplicationListenerProvider
{
    public const DEFAULT_LISTENERS = [
        'RouteListener',
        'DispatchListener',
        'HttpMethodListener',
        'ViewManager',
        'SendResponseListener',
    ];

    /**
     * @param array<string|ListenerAggregateInterface> $listeners
     */
    private function __construct(private readonly ContainerInterface $container, private readonly array $listeners)
    {
    }

    /**
     * @param array<string|ListenerAggregateInterface> $extraListeners
     */
    public static function withDefaultListeners(ContainerInterface $container, array $extraListeners): self
    {
        return new self(
            $container,
            array_unique(array_merge(self::DEFAULT_LISTENERS, $extraListeners), SORT_REGULAR)
        );
    }

    /**
     * @param array<string|ListenerAggregateInterface> $extraListeners
     */
    public static function withoutDefaultListeners(ContainerInterface $container, array $extraListeners): self
    {
        return new self(
            $container,
            array_unique($extraListeners, SORT_REGULAR)
        );
    }

    /**
     * @return array<string|ListenerAggregateInterface>
     */
    public function getListeners(): array
    {
        return $this->listeners;
    }

    public function registerListeners(Application $application): void
    {
        $events = $application->getEventManager();
        foreach ($this->listeners as $listener) {
            $msg = '';
            if (is_string($listener)) {
                $msg = sprintf(' with container id "%s"', $listener);
                /** @var mixed $listener */
                $listener = $this->container->get($listener);
            }
            if (! $listener instanceof ListenerAggregateInterface) {
                throw new DomainException(sprintf(
                    'Application listener%s expected to be instance of %s, %s given',
                    $msg,
                    ListenerAggregateInterface::class,
                    get_debug_type($listener)
                ));
            }

            $listener->attach($events);
        }
    }
}
