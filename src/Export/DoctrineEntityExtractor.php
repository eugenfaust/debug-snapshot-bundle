<?php

declare(strict_types=1);

namespace Evgenijfaustov\DebugSnapshotBundle\Export;

use Doctrine\ORM\Mapping\ClassMetadata;

final class DoctrineEntityExtractor
{
    private ScalarNormalizer $normalizer;

    public function __construct(ScalarNormalizer $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    public function extractFields(object $entity, ClassMetadata $metadata): array
    {
        $fields = [];
        $identifierFields = $metadata->getIdentifierFieldNames();

        foreach ($metadata->getFieldNames() as $field) {
            if (in_array($field, $identifierFields, true)) {
                continue;
            }

            $value = $metadata->getFieldValue($entity, $field);
            $fields[$field] = $this->normalizer->normalize($value);
        }

        return $fields;
    }
}
