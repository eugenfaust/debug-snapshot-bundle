<?php

declare(strict_types=1);

namespace Evgenijfaustov\DebugSnapshotBundle\Import;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\ClassMetadata;
use InvalidArgumentException;

final class EntityHydrator
{
    private ScalarDenormalizer $denormalizer;

    public function __construct(ScalarDenormalizer $denormalizer)
    {
        $this->denormalizer = $denormalizer;
    }

    public function hydrateScalars(object $entity, ClassMetadata $metadata, array $fields): void
    {
        foreach ($fields as $field => $value) {
            if (!$metadata->hasField($field)) {
                throw new InvalidArgumentException(sprintf('Field "%s" not found in "%s".', $field, $metadata->getName()));
            }

            $converted = $this->denormalizer->denormalize($metadata, (string) $field, $value);
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
}
