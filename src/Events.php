<?php
declare(strict_types=1);

namespace GlueApps\Components;

/**
 * @author Andy Daniel Navarro Taño <andaniel05@gmail.com>
 */
final class Events
{
    public const BEFORE_INSERTION = 'tree.before_insertion';

    public const AFTER_INSERTION = 'tree.after_insertion';

    public const BEFORE_DELETION = 'tree.before_deletion';

    public const AFTER_DELETION = 'tree.after_deletion';

    /**
     * This class will not have instances.
     */
    private function __construct() {}
}
