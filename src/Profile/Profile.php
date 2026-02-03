<?php

declare(strict_types=1);

namespace Evgenijfaustov\DebugSnapshotBundle\Profile;

final readonly class Profile
{
    public function __construct(private string $name, private string $rootClass, private int $maxDepth, private int $maxNodes, private array $include, private array $piiFields)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRootClass(): string
    {
        return $this->rootClass;
    }

    public function getMaxDepth(): int
    {
        return $this->maxDepth;
    }

    public function getMaxNodes(): int
    {
        return $this->maxNodes;
    }

    public function getInclude(): array
    {
        return $this->include;
    }

    public function getPiiFields(): array
    {
        return $this->piiFields;
    }
}
