<?php

declare(strict_types=1);

namespace Evgenijfaustov\DebugSnapshotBundle\Profile;

final class Profile
{
    private string $name;
    private string $rootClass;
    private int $maxDepth;
    private int $maxNodes;
    private array $include;
    private array $piiFields;

    public function __construct(
        string $name,
        string $rootClass,
        int $maxDepth,
        int $maxNodes,
        array $include,
        array $piiFields
    ) {
        $this->name = $name;
        $this->rootClass = $rootClass;
        $this->maxDepth = $maxDepth;
        $this->maxNodes = $maxNodes;
        $this->include = $include;
        $this->piiFields = $piiFields;
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
