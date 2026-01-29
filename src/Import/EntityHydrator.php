<?php

declare(strict_types=1);

namespace Evgenijfaustov\DebugSnapshotBundle\Import;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\ClassMetadata;
use InvalidArgumentException;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;

final class EntityHydrator
{
    private ScalarDenormalizer $denormalizer;
    private ValueDenormalizer $valueDenormalizer;

    public function __construct(ScalarDenormalizer $denormalizer, ValueDenormalizer $valueDenormalizer)
    {
        $this->denormalizer = $denormalizer;
        $this->valueDenormalizer = $valueDenormalizer;
    }

    public function hydrateScalars(object $entity, ClassMetadata $metadata, array $fields): void
    {
        foreach ($fields as $field => $value) {
            if (!$metadata->hasField($field)) {
                throw new InvalidArgumentException(sprintf('Field "%s" not found in "%s".', $field, $metadata->getName()));
            }

            $mapping = $metadata->getFieldMapping($field);
            $embeddedClass = $this->resolveEmbeddedClass($mapping);
            if ($embeddedClass !== null && ($value === null || is_scalar($value))) {
                $embeddedValue = $this->valueDenormalizer->denormalize($embeddedClass, $value);
                $metadata->setFieldValue($entity, $mapping['declaredField'], $embeddedValue);
                continue;
            }

            $converted = $this->denormalizer->denormalize($metadata, (string) $field, $value);
            $reflectionProperty = $metadata->getReflectionProperty($field);
            $targetClass = $this->resolveTargetClass($reflectionProperty?->getType());
            if ($targetClass !== null && ($converted === null || is_scalar($converted))) {
                $converted = $this->valueDenormalizer->denormalize($targetClass, $converted);
            }
            $metadata->setFieldValue($entity, $field, $converted);
        }
    }

    public function hydrateToOne(object $entity, ClassMetadata $metadata, string $field, object $target): void
    {
        if (!$metadata->hasAssociation($field)) {
            throw new InvalidArgumentException(sprintf('Association "%s" not found in "%s".', $field, $metadata->getName()));
        }

        if (!$metadata->isSingleValuedAssociation($field)) {
            throw new InvalidArgumentException(sprintf('Association "%s" in "%s" is not single-valued.', $field, $metadata->getName()));
        }

        $metadata->setFieldValue($entity, $field, $target);
    }

    public function hydrateToMany(object $entity, ClassMetadata $metadata, string $field, array $targets): void
    {
        if (!$metadata->hasAssociation($field)) {
            throw new InvalidArgumentException(sprintf('Association "%s" not found in "%s".', $field, $metadata->getName()));
        }

        if (!$metadata->isCollectionValuedAssociation($field)) {
            throw new InvalidArgumentException(sprintf('Association "%s" in "%s" is not collection-valued.', $field, $metadata->getName()));
        }

        $collection = $metadata->getFieldValue($entity, $field);
        if (!$collection instanceof Collection) {
            $collection = new ArrayCollection();
            $metadata->setFieldValue($entity, $field, $collection);
        }

        $collection->clear();
        foreach ($targets as $target) {
            $collection->add($target);
        }
    }

    private function resolveTargetClass(ReflectionType|null $type): string|null
    {
        if ($type instanceof ReflectionNamedType) {
            if ($type->isBuiltin()) {
                return null;
            }

            return $type->getName();
        }

        if ($type instanceof ReflectionUnionType) {
            foreach ($type->getTypes() as $namedType) {
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

    private function resolveEmbeddedClass(mixed $mapping): string|null
    {
        if (!is_array($mapping) && !$mapping instanceof \ArrayAccess) {
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
