<?php
declare(strict_types=1);

namespace GlueApps\Components;

/**
 * The base component class that may contain other components.
 *
 * @author Andy Daniel Navarro Taño <andaniel05@gmail.com>
 * @abstract
 */
abstract class AbstractParentComponent extends AbstractComponent
{
    /**
     * @var array
     */
    protected $children = [];

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
     * Insert a child component.
     *
     * Registers this component as parent of the child. This behavior may be
     * cancelled if second argument is specified as false.
     *
     * @param AbstractComponent $child  The child to insert.
     * @param bool $assignsParent       When is true this component is registered
     *                                  as parent of the child.
     */
    public function addChild(AbstractComponent $child, bool $assignsParent = true)
    {
        $this->children[$child->getUId()] = $child;

        if ($assignsParent) {
            $child->setParent($this, false);
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
     * Remove a child by his unique identifier.
     *
     * @param  string $uid Unique identifier
     */
    public function dropChild(string $uid)
    {
        unset($this->children[$uid]);
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
        }

        return isset($this->children[$uid]);
    }
}