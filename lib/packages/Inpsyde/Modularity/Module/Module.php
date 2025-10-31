<?php

declare(strict_types=1);

namespace Dinamiko\DKPDF\Vendor\Inpsyde\Modularity\Module;

/**
 * @package Dinamiko\DKPDF\Vendor\Inpsyde\Modularity\Module
 */
interface Module
{
    /**
     * Unique identifier for your Module.
     *
     * @return string
     */
    public function id(): string;
}
