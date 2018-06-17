<?php
declare(strict_types=1);

namespace GlueApps\Components;

/**
 * @author Andy Daniel Navarro Taño <andaniel05@gmail.com>
 */
interface DependencyInterface
{
    public function getName(): string;

    public function getVersion(): ?string;
}