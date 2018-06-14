<?php
declare(strict_types=1);

namespace GlueApps\Components\Event;

/**
 * @author Andy Daniel Navarro Taño <andaniel05@gmail.com>
 */
class BeforeDeletionEvent extends TreeMutationEvent implements CancellableEventInterface
{
    use CancellableEventTrait;
}
