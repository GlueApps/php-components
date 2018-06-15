<?php
declare(strict_types=1);

namespace GlueApps\Components\Event;

use GlueApps\Components\AbstractComponent;

/**
 * @author Andy Daniel Navarro Taño <andaniel05@gmail.com>
 */
class TreeMutationEvent extends Event
{
    protected $parent;

    protected $child;

    public function __construct(AbstractComponent $target, AbstractComponent $parent, AbstractComponent $child)
    {
        parent::__construct($target);

        $this->parent = $parent;
        $this->child = $child;
    }

    public function getParent(): AbstractComponent
    {
        return $this->parent;
    }

    public function getChild(): AbstractComponent
    {
        return $this->child;
    }
}
