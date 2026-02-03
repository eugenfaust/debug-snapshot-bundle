<?php

declare(strict_types=1);

namespace Evgenijfaustov\DebugSnapshotBundle\Snapshot;

final readonly class Snapshot
{
    public const string FORMAT = 'debug-snapshot/1';

    public function __construct(private EntityReference $root, private array $entities)
    {
    }

    public function getRoot(): EntityReference
    {
        return $this->root;
    }

    public function getEntities(): array
    {
        return $this->entities;
    }

    public function toArray(): array
    {
        $entities = [];
        foreach ($this->entities as $entity) {
            $entities[] = $entity->toArray();
        }

        return [
            'format' => self::FORMAT,
            'root' => $this->root->toArray(),
            'entities' => $entities,
        ];
    }
}
