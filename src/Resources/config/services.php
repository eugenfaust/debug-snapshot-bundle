<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->bind('$hydrateMap', '%debug_snapshot.hydrate%')
        ->bind('$httpEnabled', '%debug_snapshot.http.enabled%')
        ->bind('$httpRole', '%debug_snapshot.http.role%');

    $services->load('Evgenijfaustov\\DebugSnapshotBundle\\', '../../*')
        ->exclude([
            '../../DependencyInjection',
            '../../Resources',
            '../../Tests',
            '../../Profile',
        ]);
};
