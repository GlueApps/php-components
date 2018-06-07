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
     * Inserts a child component.
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
     * Removes a child by his unique identifier.
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
                if ($component instanceof AbstractParentComponent) {
                    yield from $generator($component->children());
                }
            }

            return;
        };

        return $generator($this->children);
    }

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
