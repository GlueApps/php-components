<?php
declare(strict_types=1);

namespace GlueApps\Components\Event;

use GlueApps\Components\AbstractComponent;
use GlueApps\Components\AbstractParentComponent;

/**
 * @author Andy Daniel Navarro Taño <andaniel05@gmail.com>
 */
class MutationEvent extends Event
{
    protected $parent;

    protected $child;

    public function __construct(AbstractComponent $target, AbstractParentComponent $parent, AbstractComponent $child)
    {
        parent::__construct($target);

        $this->parent = $parent;
        $this->child = $child;
    }

    public function getParent(): AbstractParentComponent
    {
        return $this->parent;
    }

    public function getChild(): AbstractComponent
    {
        return $this->child;
    }
}
