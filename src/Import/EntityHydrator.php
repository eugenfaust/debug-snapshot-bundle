<?php

declare(strict_types=1);

namespace Evgenijfaustov\DebugSnapshotBundle\Import;

use ArrayAccess;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\ClassMetadata;
use InvalidArgumentException;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;

final readonly class EntityHydrator
{
    public function __construct(private ScalarDenormalizer $denormalizer, private ValueDenormalizer $valueDenormalizer)
    {
    }

    public function hydrateScalars(object $entity, ClassMetadata $classMetadata, array $fields): void
    {
        foreach ($fields as $field => $value) {
            if (!$classMetadata->hasField($field)) {
                throw new InvalidArgumentException(sprintf('Field "%s" not found in "%s".', $field, $classMetadata->getName()));
            }

            $mapping = $classMetadata->getFieldMapping($field);
            $embeddedClass = $this->resolveEmbeddedClass($mapping);
            if ($embeddedClass !== null && ($value === null || is_scalar($value))) {
                $embeddedValue = $this->valueDenormalizer->denormalize($embeddedClass, $value);
                $classMetadata->setFieldValue($entity, $mapping['declaredField'], $embeddedValue);

                continue;
            }

            $converted = $this->denormalizer->denormalize($classMetadata, (string) $field, $value);
            $reflectionProperty = $classMetadata->getReflectionProperty($field);
            $targetClass = $this->resolveTargetClass($reflectionProperty?->getType());
            if ($targetClass !== null && ($converted === null || is_scalar($converted))) {
                $converted = $this->valueDenormalizer->denormalize($targetClass, $converted);
            }
            $classMetadata->setFieldValue($entity, $field, $converted);
        }
    }

    public function hydrateToOne(object $entity, ClassMetadata $classMetadata, string $field, object $target): void
    {
        if (!$classMetadata->hasAssociation($field)) {
            throw new InvalidArgumentException(sprintf('Association "%s" not found in "%s".', $field, $classMetadata->getName()));
        }

        if (!$classMetadata->isSingleValuedAssociation($field)) {
            throw new InvalidArgumentException(sprintf('Association "%s" in "%s" is not single-valued.', $field, $classMetadata->getName()));
        }

        $classMetadata->setFieldValue($entity, $field, $target);
    }

    public function hydrateToMany(object $entity, ClassMetadata $classMetadata, string $field, array $targets): void
    {
        if (!$classMetadata->hasAssociation($field)) {
            throw new InvalidArgumentException(sprintf('Association "%s" not found in "%s".', $field, $classMetadata->getName()));
        }

        if (!$classMetadata->isCollectionValuedAssociation($field)) {
            throw new InvalidArgumentException(sprintf('Association "%s" in "%s" is not collection-valued.', $field, $classMetadata->getName()));
        }

        $collection = $classMetadata->getFieldValue($entity, $field);
        if (!$collection instanceof Collection) {
            $collection = new ArrayCollection();
            $classMetadata->setFieldValue($entity, $field, $collection);
        }

        $collection->clear();
        foreach ($targets as $target) {
            $collection->add($target);
        }
    }

    private function resolveTargetClass(?ReflectionType $reflectionType): ?string
    {
        if ($reflectionType instanceof ReflectionNamedType) {
            if ($reflectionType->isBuiltin()) {
                return null;
            }

            return $reflectionType->getName();
        }

        if ($reflectionType instanceof ReflectionUnionType) {
            foreach ($reflectionType->getTypes() as $namedType) {
                if (!$namedType instanceof ReflectionNamedType) {
                    continue;
                }

                if ($namedType->isBuiltin()) {
                    continue;
                }

                $name = $namedType->getName();
                if ($name === 'null') {
                    continue;
                }

                return $name;
            }
        }

        return null;
    }

    private function resolveEmbeddedClass(mixed $mapping): ?string
    {
        if (!is_array($mapping) && !$mapping instanceof ArrayAccess) {
            return null;
        }

        if (!isset($mapping['declaredField'], $mapping['originalClass'], $mapping['fieldName'])) {
            return null;
        }

        if (!is_string($mapping['declaredField']) || !is_string($mapping['originalClass'])) {
            return null;
        }

        if ($mapping['declaredField'] === $mapping['fieldName']) {
            return null;
        }

        return $mapping['originalClass'];
    }
}
