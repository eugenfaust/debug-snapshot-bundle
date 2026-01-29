<?php

declare(strict_types=1);

namespace Evgenijfaustov\DebugSnapshotBundle\Snapshot;

final class SnapshotEntity
{
    private string $class;
    private string|int $id;
    private array $fields;
    private array $toOne;
    private array $toMany;

    public function __construct(
        string $class,
        string|int $id,
        array $fields,
        array $toOne,
        array $toMany
    ) {
        $this->class = $class;
        $this->id = $id;
        $this->fields = $fields;
        $this->toOne = $toOne;
        $this->toMany = $toMany;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getId(): string|int
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
