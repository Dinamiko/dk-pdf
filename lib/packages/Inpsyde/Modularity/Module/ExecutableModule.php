<?php

declare(strict_types=1);

namespace Dinamiko\DKPDF\Vendor\Inpsyde\Modularity\Module;

use Dinamiko\DKPDF\Vendor\Psr\Container\ContainerInterface;

interface ExecutableModule extends Module
{
    /**
     * Perform actions with objects retrieved from the container. Usually, adding WordPress hooks.
     * Return true to signal a success, false to signal a failure.
     *
     * @param ContainerInterface $container
     *
     * @return bool     true when successfully booted, otherwise false.
     */
    public function run(ContainerInterface $container): bool;
}
