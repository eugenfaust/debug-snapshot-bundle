<?php

declare(strict_types=1);

namespace Evgenijfaustov\DebugSnapshotBundle\Export;

use Doctrine\ORM\Mapping\ClassMetadata;

final readonly class DoctrineEntityExtractor
{
    public function __construct(private ScalarNormalizer $normalizer)
    {
    }

    public function extractFields(object $entity, ClassMetadata $classMetadata): array
    {
        $fields = [];
        $identifierFields = $classMetadata->getIdentifierFieldNames();

        foreach ($classMetadata->getFieldNames() as $field) {
            if (in_array($field, $identifierFields, true)) {
                continue;
            }

            $value = $classMetadata->getFieldValue($entity, $field);
            $fields[$field] = $this->normalizer->normalize($value);
        }

        return $fields;
    }
}
