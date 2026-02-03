<?php

declare(strict_types=1);

namespace Evgenijfaustov\DebugSnapshotBundle\Snapshot;

final readonly class EntityReference
{
    public function __construct(private string $class, private int|string $id)
    {
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getId(): int|string
    {
        return $this->id;
    }

    public function toArray(): array
    {
        return [
            'class' => $this->class,
            'id' => $this->id,
        ];
    }
}
