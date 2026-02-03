<?php

declare(strict_types=1);

namespace Evgenijfaustov\DebugSnapshotBundle\Import;

use BackedEnum;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;

final readonly class ScalarDenormalizer
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function denormalize(ClassMetadata $classMetadata, string $field, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $fieldMapping = $classMetadata->getFieldMapping($field);

        if (isset($fieldMapping['enumType']) && is_string($fieldMapping['enumType']) && enum_exists($fieldMapping['enumType'])) {
            $enumClass = $fieldMapping['enumType'];
            if (is_subclass_of($enumClass, BackedEnum::class)) {
                return $enumClass::from($value);
            }

            return constant($enumClass.'::'.$value);
        }

        $typeName = $classMetadata->getTypeOfField($field);
        if (is_string($typeName) && Type::hasType($typeName)) {
            $type = Type::getType($typeName);

            return $type->convertToPHPValue($value, $this->entityManager->getConnection()->getDatabasePlatform());
        }

        return $value;
    }
}
