<?php
declare(strict_types=1);

namespace GlueApps\Components;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * The base component class.
 *
 * @author Andy Daniel Navarro Taño <andaniel05@gmail.com>
 * @abstract
 */
abstract class AbstractComponent
{
    /**
     * Unique identifier.
     *
     * @var string
     */
    private $uid;

    /**
     * @var ?AbstractComponent
     */
    protected $parent;

    /**
     * @var EventDispatcher
     */
    protected $dispatcher;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->dispatcher = new EventDispatcher;
    }

    /**
     * Returns the unique identifier of the component.
     *
     * @return string
     */
    public function getUId(): string
    {
        if (null === $this->uid) {
            $reflection = new \ReflectionClass(static::class);
            if ($reflection->isAnonymous()) {
                $this->uid = uniqid('anonymous');
            } else {
                $this->uid = uniqid(strtolower(basename(static::class)));
            }
        }

        return $this->uid;
    }

    /**
     * Returns the parent.
     *
     * @return ?AbstractParentComponent
     */
    public function getParent(): ?AbstractParentComponent
    {
        return $this->parent;
    }

    /**
     * Assigns the parent.
     *
     * Registers this component as child of the parent. This behavior may be
     * cancelled if second argument is specified as false.
     *
     * @param ?AbstractParentComponent $parent  The parent.
     * @param bool $registersChild              When is true this component will
     *                                          be registered as child of the parent.
     */
    public function setParent(?AbstractParentComponent $parent, bool $registersChild = true)
    {
        $this->parent = $parent;

        if ($registersChild) {
            $parent->addChild($this, false);
        }
    }

    /**
     * Returns the event dispatcher.
     *
     * @return EventDispatcherInterface
     */
    public function getDispatcher(): EventDispatcherInterface
    {
        return $this->dispatcher;
    }

    /**
     * Assigns the event dispatcher.
     *
     * @param EventDispatcherInterface $dispatcher
     */
    public function setDispatcher(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Dispatchs an event on this component.
     *
     * @param  string     $eventName
     * @param  Event|null $event
     */
    public function dispatch(string $eventName, Event $event = null)
    {
        $this->dispatcher->dispatch($eventName, $event);
    }
}
