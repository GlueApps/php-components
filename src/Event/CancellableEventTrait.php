<?php
declare(strict_types=1);

namespace GlueApps\Components\Event;

/**
 * @author Andy Daniel Navarro Taño <andaniel05@gmail.com>
 */
trait CancellableEventTrait
{
    protected $cancelled = false;

    public function isCancelled(): bool
    {
        return $this->cancelled;
    }

    public function cancel(bool $cancelled = true)
    {
        $this->cancelled = $cancelled;
    }
}
