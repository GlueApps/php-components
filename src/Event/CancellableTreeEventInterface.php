<?php
declare(strict_types=1);

namespace GlueApps\Components\Event;

/**
 * @author Andy Daniel Navarro Taño <andaniel05@gmail.com>
 */
interface CancellableTreeEventInterface
{
    public function isCancelled(): bool;

    public function cancel(bool $cancelled = true);
}
