<?php

declare(strict_types=1);

namespace Evgenijfaustov\DebugSnapshotBundle\DependencyInjection;

use Evgenijfaustov\DebugSnapshotBundle\Profile\Profile;
use Evgenijfaustov\DebugSnapshotBundle\Profile\ProfileRegistry;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\Config\FileLocator;

final class DebugSnapshotExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('debug_snapshot.enabled', $config['enabled']);

        $profiles = [];
        foreach ($config['profiles'] as $name => $profileConfig) {
            $profiles[$name] = new Definition(Profile::class, [
                $name,
                $profileConfig['root_class'],
                $profileConfig['max_depth'],
                $profileConfig['max_nodes'],
                $profileConfig['include'],
                $profileConfig['pii_fields'],
            ]);
        }

        $container->setDefinition(ProfileRegistry::class, new Definition(ProfileRegistry::class, [$profiles]));

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.php');
    }
}
