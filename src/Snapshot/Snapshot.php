<?php

declare(strict_types=1);

namespace Evgenijfaustov\DebugSnapshotBundle\Snapshot;

final class Snapshot
{
    public const FORMAT = 'debug-snapshot/1';

    private EntityReference $root;
    private array $entities;

    public function __construct(EntityReference $root, array $entities)
    {
        $this->root = $root;
        $this->entities = $entities;
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
