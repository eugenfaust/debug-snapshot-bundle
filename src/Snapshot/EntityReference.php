<?php

declare(strict_types=1);

namespace Evgenijfaustov\DebugSnapshotBundle\Snapshot;

final class EntityReference
{
    private string $class;
    private string|int $id;

    public function __construct(string $class, string|int $id)
    {
        $this->class = $class;
        $this->id = $id;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getId(): string|int
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
