<?php

declare(strict_types=1);

namespace Evgenijfaustov\DebugSnapshotBundle\Import;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Evgenijfaustov\DebugSnapshotBundle\Snapshot\Snapshot;
use RuntimeException;

final readonly class DoctrineSnapshotImporter
{
    public function __construct(private EntityManagerInterface $entityManager, private EntityHydrator $hydrator)
    {
    }

    public function import(array $snapshotData): void
    {
        if (($snapshotData['format'] ?? null) !== Snapshot::FORMAT) {
            throw new RuntimeException('Unsupported snapshot format.');
        }

        $entities = $snapshotData['entities'] ?? [];
        if (!is_array($entities)) {
            throw new RuntimeException('Invalid snapshot payload.');
        }

        $map = [];
        foreach ($entities as $entityData) {
            $class = $entityData['class'] ?? null;
            $id = $entityData['id'] ?? null;
            $fields = $entityData['fields'] ?? [];

            if (!is_string($class) || ($id === null)) {
                throw new RuntimeException('Invalid entity data in snapshot.');
            }

            $metadata = $this->entityManager->getClassMetadata($class);
            $this->assertSingleIdentifier($metadata);

            $entity = $this->entityManager->find($class, $id);
            if ($entity === null) {
                $entity = $metadata->newInstance();
                $this->setIdentifier($entity, $metadata, $id);
                $this->entityManager->persist($entity);
            }

            $this->hydrator->hydrateScalars($entity, $metadata, $fields);

            $map[$class][(string) $id] = $entity;
        }

        $counter = 0;

        foreach ($entities as $entityData) {
            $class = $entityData['class'] ?? null;
            $id = $entityData['id'] ?? null;
            $relations = $entityData['relations'] ?? [];
            $toOne = $relations['toOne'] ?? [];
            $toMany = $relations['toMany'] ?? [];

            if (!is_string($class) || ($id === null)) {
                throw new RuntimeException('Invalid entity data in snapshot.');
            }

            $entity = $map[$class][(string) $id] ?? null;
            if ($entity === null) {
                throw new RuntimeException('Entity not found during import.');
            }

            $metadata = $this->entityManager->getClassMetadata($class);

            foreach ($toOne as $field => $reference) {
                if ($reference === null) {
                    continue;
                }

                $targetClass = $reference['class'] ?? null;
                $targetId = $reference['id'] ?? null;

                if (!is_string($targetClass) || ($targetId === null)) {
                    throw new RuntimeException('Invalid toOne reference in snapshot.');
                }

                $target = $map[$targetClass][(string) $targetId] ?? null;
                if ($target === null) {
                    throw new RuntimeException('Referenced entity not found during import.');
                }

                $this->hydrator->hydrateToOne($entity, $metadata, (string) $field, $target);
            }

            foreach ($toMany as $field => $references) {
                if (!is_array($references)) {
                    throw new RuntimeException('Invalid toMany reference in snapshot.');
                }

                $targets = [];
                foreach ($references as $reference) {
                    $targetClass = $reference['class'] ?? null;
                    $targetId = $reference['id'] ?? null;

                    if (!is_string($targetClass) || ($targetId === null)) {
                        throw new RuntimeException('Invalid toMany reference in snapshot.');
                    }

                    $target = $map[$targetClass][(string) $targetId] ?? null;
                    if ($target === null) {
                        throw new RuntimeException('Referenced entity not found during import.');
                    }

                    $targets[] = $target;
                }

                $this->hydrator->hydrateToMany($entity, $metadata, (string) $field, $targets);
            }

            ++$counter;
            if ($counter % 200 === 0) {
                $this->entityManager->flush();
            }
        }

        $this->entityManager->flush();
    }

    private function setIdentifier(object $entity, ClassMetadata $classMetadata, int|string $id): void
    {
        $identifier = $classMetadata->getIdentifierFieldNames();
        if (count($identifier) !== 1) {
            throw new RuntimeException('Composite identifier is not supported.');
        }

        $this->hydrator->hydrateScalars($entity, $classMetadata, [$identifier[0] => $id]);
    }

    private function assertSingleIdentifier(ClassMetadata $classMetadata): void
    {
        if ($classMetadata->isIdentifierComposite) {
            throw new RuntimeException(sprintf('Composite identifier is not supported for "%s".', $classMetadata->getName()));
        }
    }
}
