<?php
declare(strict_types=1);

namespace GlueApps\Components\Event;

use GlueApps\Components\AbstractComponent;
use GlueApps\Components\AbstractParentComponent;
use Symfony\Component\EventDispatcher\Event;

/**
 * @author Andy Daniel Navarro Taño <andaniel05@gmail.com>
 */
class TreeEvent extends Event
{
    protected $parent;

    protected $child;

    public function __construct(AbstractParentComponent $parent, AbstractComponent $child)
    {
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
