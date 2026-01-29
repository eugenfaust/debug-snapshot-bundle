<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->bind('$hydrateMap', '%debug_snapshot.hydrate%');

    $services->load('Evgenijfaustov\\DebugSnapshotBundle\\', '../../*')
        ->exclude([
            '../../DependencyInjection',
            '../../Resources',
            '../../Tests',
            '../../Profile',
        ]);
};
