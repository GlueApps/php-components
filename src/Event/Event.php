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
    protected $source;

    public function __construct(AbstractComponent $source)
    {
        $this->source = $source;
    }

    public function getSource(): AbstractComponent
    {
        return $this->source;
    }
}
