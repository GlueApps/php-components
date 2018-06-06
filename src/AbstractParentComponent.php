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
     * @param AbstractComponent $child
     */
    public function addChild(AbstractComponent $child)
    {
        $this->children[$child->getUId()] = $child;
    }

    /**
     * Search a child by his unique identifier.
     *
     * @param  string $uid
     * @return ?AbstractComponent
     */
    public function getChild(string $uid): ?AbstractComponent
    {
        return $this->children[$uid] ?? null;
    }

    /**
     * Remove a child by his unique identifier.
     *
     * @param  string $uid
     */
    public function dropChild(string $uid)
    {
        unset($this->children[$uid]);
    }
}
