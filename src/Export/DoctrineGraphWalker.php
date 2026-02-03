<?php

declare(strict_types=1);

namespace Evgenijfaustov\DebugSnapshotBundle\Export;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Evgenijfaustov\DebugSnapshotBundle\Profile\Profile;
use Evgenijfaustov\DebugSnapshotBundle\Snapshot\EntityReference;
use Evgenijfaustov\DebugSnapshotBundle\Snapshot\Snapshot;
use Evgenijfaustov\DebugSnapshotBundle\Snapshot\SnapshotEntity;
use InvalidArgumentException;
use RuntimeException;
use SplQueue;

final readonly class DoctrineGraphWalker
{
    public function __construct(private EntityManagerInterface $entityManager, private DoctrineEntityExtractor $extractor)
    {
    }

    public function walk(Profile $profile, int|string $rootId): Snapshot
    {
        $rootClass = $profile->getRootClass();
        $rootEntity = $this->findEntity($rootClass, $rootId);

        $queue = new SplQueue();
        $queue->enqueue([$rootEntity, 0]);

        $visited = [];
        $entities = [];
        $nodes = 0;

        while (!$queue->isEmpty()) {
            [$entity, $depth] = $queue->dequeue();

            $metadata = $this->entityManager->getClassMetadata($entity::class);
            $class = $metadata->getName();
            $this->assertSingleIdentifier($metadata);

            $id = $this->getEntityId($entity, $metadata);
            $key = $class.':'.$id;

            if (isset($visited[$key])) {
                continue;
            }

            if ($nodes >= $profile->getMaxNodes()) {
                throw new RuntimeException('Max nodes limit exceeded.');
            }

            $visited[$key] = true;
            ++$nodes;

            $relations = [
                'toOne' => [],
                'toMany' => [],
            ];

            if ($depth < $profile->getMaxDepth()) {
                $relations = $this->collectRelations($entity, $metadata, $profile, $queue, $depth);
            }

            // Ensure lazy-loaded entities are initialized before reading scalar fields.
            $this->entityManager->initializeObject($entity);
            $fields = $this->extractor->extractFields($entity, $metadata);

            $entities[] = new SnapshotEntity(
                $class,
                $id,
                $fields,
                $relations['toOne'],
                $relations['toMany']
            );
        }

        $entityReference = new EntityReference($rootClass, $rootId);

        return new Snapshot($entityReference, $entities);
    }

    private function collectRelations(
        object $entity,
        ClassMetadata $classMetadata,
        Profile $profile,
        SplQueue $queue,
        int $depth
    ): array {
        $include = $profile->getInclude();
        $allowed = $include[$classMetadata->getName()] ?? [];

        $toOne = [];
        $toMany = [];

        foreach ($allowed as $association) {
            if (!$classMetadata->hasAssociation($association)) {
                throw new InvalidArgumentException(sprintf('Association "%s" not found in "%s".', $association, $classMetadata->getName()));
            }

            $mapping = $classMetadata->getAssociationMapping($association);
            $value = $classMetadata->getFieldValue($entity, $association);

            if ($classMetadata->isSingleValuedAssociation($association)) {
                if ($value === null) {
                    continue;
                }

                $targetMetadata = $this->entityManager->getClassMetadata($mapping['targetEntity']);
                $this->assertSingleIdentifier($targetMetadata);
                $targetId = $this->getEntityId($value, $targetMetadata);

                $reference = new EntityReference($targetMetadata->getName(), $targetId);
                $toOne[$association] = $reference;
                $queue->enqueue([$value, $depth + 1]);

                continue;
            }

            if (!$classMetadata->isCollectionValuedAssociation($association)) {
                throw new InvalidArgumentException(sprintf('Association "%s" in "%s" is not supported.', $association, $classMetadata->getName()));
            }

            if ($value === null) {
                $toMany[$association] = [];

                continue;
            }

            if ($value instanceof Collection) {
                $value = $value->toArray();
            }

            if (!is_iterable($value)) {
                throw new InvalidArgumentException(sprintf('Association "%s" in "%s" is not iterable.', $association, $classMetadata->getName()));
            }

            $items = [];
            foreach ($value as $item) {
                if (!is_object($item)) {
                    continue;
                }

                $targetMetadata = $this->entityManager->getClassMetadata($item::class);
                $this->assertSingleIdentifier($targetMetadata);
                $targetId = $this->getEntityId($item, $targetMetadata);

                $items[] = new EntityReference($targetMetadata->getName(), $targetId);
                $queue->enqueue([$item, $depth + 1]);
            }

            $toMany[$association] = $items;
        }

        return [
            'toOne' => $toOne,
            'toMany' => $toMany,
        ];
    }

    private function findEntity(string $class, int|string $id): object
    {
        $entity = $this->entityManager->find($class, $id);

        if ($entity === null) {
            throw new RuntimeException(sprintf('Entity "%s" with id "%s" not found.', $class, (string) $id));
        }

        return $entity;
    }

    private function getEntityId(object $entity, ClassMetadata $classMetadata): int|string
    {
        $values = $classMetadata->getIdentifierValues($entity);

        if (count($values) !== 1) {
            throw new RuntimeException(sprintf('Composite identifier is not supported for "%s".', $classMetadata->getName()));
        }

        $id = array_values($values)[0];

        if (is_int($id) || is_string($id)) {
            return $id;
        }

        if (is_object($id) && method_exists($id, '__toString')) {
            return (string) $id;
        }

        throw new RuntimeException(sprintf('Unsupported identifier type for "%s".', $classMetadata->getName()));
    }

    private function assertSingleIdentifier(ClassMetadata $classMetadata): void
    {
        if ($classMetadata->isIdentifierComposite) {
            throw new RuntimeException(sprintf('Composite identifier is not supported for "%s".', $classMetadata->getName()));
        }
    }
}
