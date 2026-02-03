<?php

declare(strict_types=1);

namespace Evgenijfaustov\DebugSnapshotBundle\Snapshot;

final readonly class SnapshotEntity
{
    public function __construct(private string $class, private int|string $id, private array $fields, private array $toOne, private array $toMany)
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

    public function getFields(): array
    {
        return $this->fields;
    }

    public function getToOne(): array
    {
        return $this->toOne;
    }

    public function getToMany(): array
    {
        return $this->toMany;
    }

    public function toArray(): array
    {
        $toOne = [];
        foreach ($this->toOne as $field => $reference) {
            $toOne[$field] = $reference->toArray();
        }

        $toMany = [];
        foreach ($this->toMany as $field => $references) {
            $items = [];
            foreach ($references as $reference) {
                $items[] = $reference->toArray();
            }
            $toMany[$field] = $items;
        }

        return [
            'class' => $this->class,
            'id' => $this->id,
            'fields' => $this->fields,
            'relations' => [
                'toOne' => $toOne,
                'toMany' => $toMany,
            ],
        ];
    }
}
