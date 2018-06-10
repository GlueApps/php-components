<?php
declare(strict_types=1);

namespace GlueApps\Components\Event;

use GlueApps\Components\AbstractComponent;
use Symfony\Component\EventDispatcher\Event;

/**
 * @author Andy Daniel Navarro Taño <andaniel05@gmail.com>
 */
class TreeEvent extends Event
{
    protected $target;

    public function __construct(AbstractComponent $target)
    {
        $this->target = $target;
    }

    public function getTarget(): AbstractComponent
    {
        return $this->target;
    }
}
