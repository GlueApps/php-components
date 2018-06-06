<?php
declare(strict_types=1);

namespace GlueApps\Components;

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
     * @param bool $registersChild              When is true this component will be registered as child of the parent.
     */
    public function setParent(?AbstractParentComponent $parent, bool $registersChild = true)
    {
        $this->parent = $parent;

        if ($registersChild) {
            $parent->addChild($this, false);
        }
    }
}
