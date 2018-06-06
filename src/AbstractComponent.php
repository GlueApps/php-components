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
     * @return ?AbstractComponent
     */
    public function getParent(): ?AbstractComponent
    {
        return $this->parent;
    }

    /**
     * Assigns the parent.
     *
     * @param ?AbstractComponent $parent
     */
    public function setParent(?AbstractComponent $parent)
    {
        $this->parent = $parent;
    }
}
