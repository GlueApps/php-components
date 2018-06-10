<?php
declare(strict_types=1);

namespace GlueApps\Components\Event;

use GlueApps\Components\AbstractComponent;
use Symfony\Component\EventDispatcher\Event as SymfonyEvent;

/**
 * @author Andy Daniel Navarro Taño <andaniel05@gmail.com>
 */
class Event extends SymfonyEvent
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
