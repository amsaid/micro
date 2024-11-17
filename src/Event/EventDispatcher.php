<?php

namespace SdFramework\Event;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;

class EventDispatcher implements EventDispatcherInterface
{
    private array $listeners = [];

    public function dispatch(object $event)
    {
        $eventClass = get_class($event);

        if (isset($this->listeners[$eventClass])) {
            foreach ($this->listeners[$eventClass] as $listener) {
                if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
                    break;
                }
                $listener($event);
            }
        }

        return $event;
    }

    public function addListener(string $eventClass, callable $listener): void
    {
        $this->listeners[$eventClass][] = $listener;
    }

    public function getListeners(string $eventClass): array
    {
        return $this->listeners[$eventClass] ?? [];
    }
}
