<?php
declare(strict_types=1);

namespace GlueApps\Components;

use GlueApps\Components\Event\TreeEvent;
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
     * Occurs before that a component be inserted in any node of the tree.
     * Permits cancel the operation.
     */
    public const EVENT_BEFORE_INSERTION = 'tree.before_insertion';

    /**
     * Occurs after that a component was inserted in any node of the tree.
     */
    public const EVENT_AFTER_INSERTION = 'tree.after_insertion';

    /**
     * Occurs before that a component be deleted from any node of the tree.
     * Permits cancel the operation.
     */
    public const EVENT_BEFORE_DELETION = 'tree.before_deletion';

    /**
     * Occurs after that a component was deleted from any node of the tree.
     */
    public const EVENT_AFTER_DELETION = 'tree.after_deletion';

    /**
     * Unique identifier.
     *
     * @var string
     */
    protected $uid;

    /**
     * @var ?AbstractComponent
     */
    protected $parent;

    /**
     * @var EventDispatcher
     */
    protected $dispatcher;

    /**
     * @var array
     */
    protected $children = [];

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->generateUId();

        $this->dispatcher = new EventDispatcher;
    }

    /**
     * Returns the unique identifier of the component.
     *
     * @return string
     */
    public function getUId(): string
    {
        return $this->uid;
    }

    /**
     * Sets the unique identifier.
     *
     * @param string $uid
     */
    public function setUId(string $uid)
    {
        $this->uid = $uid;
    }

    /**
     * Creates a unique identifier for the component.
     *
     * This method should be override to define a custom generation method.
     */
    protected function generateUId(): void
    {
        $this->uid = uniqid();
    }

    /**
     * Returns the parent.
     *
     * @return ?AbstractComponent
     */
    public function getParent(): ?AbstractComponent
    {
        return $this->parent;
    }

    /**
     * Assigns the parent.
     *
     * Registers this component as child of the parent. This behavior may be
     * cancelled if second argument is specified as false.
     *
     * @param ?AbstractComponent $parent  The parent.
     * @param bool $registersChild        When is true this component will
     *                                    be registered as child of the parent.
     */
    public function setParent(?AbstractComponent $parent, bool $registersChild = true)
    {
        if ($parent === null && $this->parent instanceof AbstractComponent) {
            $this->parent->dropChild($this);
        }

        if ($registersChild && $parent) {
            if ($parent->addChild($this, false)) {
                $this->parent = $parent;
            }
        } else {
            $this->parent = $parent;
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
     * @param  string       $eventName
     * @param  ?TreeEvent   $event
     */
    public function dispatch(string $eventName, ?TreeEvent $event = null)
    {
        $this->dispatcher->dispatch($eventName, $event);
    }

    /**
     * Returns all the parents until the root.
     *
     * @return array
     */
    public function parents(): array
    {
        $result = [];
        $parent = $this->parent;

        while ($parent instanceof AbstractComponent) {
            $result[$parent->getUId()] = $parent;
            $parent = $parent->getParent();
        }

        return $result;
    }

    /**
     * Returns the root component of the tree.
     *
     * If the component has not a parent then returns null.
     *
     * @return ?AbstractComponent
     */
    public function getRoot(): ?AbstractComponent
    {
        $parents = $this->parents();

        return empty($parents) ? null : array_pop($parents);
    }

    /**
     * Registers a listener for an event.
     *
     * @param  string   $eventName
     * @param  callable $listener
     */
    public function on(string $eventName, callable $listener)
    {
        $this->dispatcher->addListener($eventName, $listener);
    }

    /**
     * Returns the children components.
     *
     * @return array
     */
    public function children(): array
    {
        return $this->children;
    }

    /**
     * Inserts a child component.
     *
     * Registers this component as parent of the child. This behavior may be
     * cancelled if second argument is specified as false.
     *
     * @param AbstractComponent $child  The child to insert.
     * @param boolean $assignsParent    When is true this component is registered
     *                                  as parent of the child.
     *
     * @return boolean                  Returns true if insertion is success and false otherwise.
     */
    public function addChild(AbstractComponent $child, bool $assignsParent = true): bool
    {
        $beforeInsertionEvent = new Event\BeforeInsertionEvent($this, $this, $child);
        $this->dispatcher->dispatch(self::EVENT_BEFORE_INSERTION, $beforeInsertionEvent);

        if ($beforeInsertionEvent->isCancelled()) {
            return false;
        }

        $this->children[$child->getUId()] = $child;

        $afterInsertionEvent = new Event\AfterInsertionEvent($this, $this, $child);
        $this->dispatcher->dispatch(self::EVENT_AFTER_INSERTION, $afterInsertionEvent);

        if ($assignsParent) {
            $child->setParent($this, false);
        }

        return true;
    }

    /**
     * Inserts several childs at once.
     *
     * @param array $childs Child List
     */
    public function addChilds(...$childs)
    {
        foreach ($childs as $child) {
            if ($child instanceof AbstractComponent) {
                $this->addChild($child);
            }
        }
    }

    /**
     * Search a child by his unique identifier.
     *
     * @param  string $uid Unique identifier
     * @return ?AbstractComponent
     */
    public function getChild(string $uid): ?AbstractComponent
    {
        return $this->children[$uid] ?? null;
    }

    /**
     * Removes a child.
     *
     * @param AbstractComponent|string  $child Indicates the child instance or his unique identifier.
     * @return boolean                  Return true if deletion is success.
     */
    public function dropChild($child): bool
    {
        if (is_string($child)) {
            $child = $this->children[$child] ?? null;
        }

        if (! $child instanceof AbstractComponent ||
            ! array_search($child, $this->children)) {
            return false;
        }

        $beforeDeletionEvent = new Event\BeforeDeletionEvent($this, $this, $child);
        $this->dispatcher->dispatch(self::EVENT_BEFORE_DELETION, $beforeDeletionEvent);
        if ($beforeDeletionEvent->isCancelled()) {
            return false;
        }

        unset($this->children[$child->getUId()]);

        $afterDeletionEvent = new Event\AfterDeletionEvent($this, $this, $child);
        $this->dispatcher->dispatch(self::EVENT_AFTER_DELETION, $afterDeletionEvent);

        foreach ($child->parents() as $parent) {
            $parent->getDispatcher()->dispatch(self::EVENT_AFTER_DELETION, $afterDeletionEvent);
        }

        return true;
    }

    /**
     * Checks if contains the searched child.
     *
     * @param  AbstractComponent|string  $child
     * @return boolean
     */
    public function hasChild($child): bool
    {
        if (is_string($child)) {
            $uid = $child;
        } elseif ($child instanceof AbstractComponent) {
            $uid = $child->getUId();
        } else {
            return false;
        }

        return isset($this->children[$uid]);
    }

    /**
     * Iterate over each child of the tree.
     *
     * @return iterable
     */
    public function traverse(): iterable
    {
        $generator = function (array $components) use (&$generator) {
            foreach ($components as $component) {
                yield $component;
                if ($component instanceof AbstractComponent) {
                    yield from $generator($component->children());
                }
            }

            return;
        };

        return $generator($this->children);
    }

    /**
     * Does search a component in all the tree by his unique identifier.
     *
     * @param  string $uid          The unique identifier
     * @return ?AbstractComponent
     */
    public function getComponentByUId(string $uid): ?AbstractComponent
    {
        $result = null;

        foreach ($this->traverse() as $component) {
            if ($component->getUId() === $uid) {
                $result = $component;
                break;
            }
        }

        return $result;
    }
}
