<?php

declare(strict_types=1);

namespace Evgenijfaustov\DebugSnapshotBundle\Import;

use BackedEnum;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use UnitEnum;

final class ScalarDenormalizer
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function denormalize(ClassMetadata $metadata, string $field, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $mapping = $metadata->getFieldMapping($field);

        if (isset($mapping['enumType']) && is_string($mapping['enumType']) && enum_exists($mapping['enumType'])) {
            $enumClass = $mapping['enumType'];
            if (is_subclass_of($enumClass, BackedEnum::class)) {
                return $enumClass::from($value);
            }
            if (is_subclass_of($enumClass, UnitEnum::class)) {
                return constant($enumClass . '::' . $value);
            }
        }

        $typeName = $metadata->getTypeOfField($field);
        if (is_string($typeName) && Type::hasType($typeName)) {
            $type = Type::getType($typeName);
            return $type->convertToPHPValue($value, $this->entityManager->getConnection()->getDatabasePlatform());
        }

        return $value;
    }
}
