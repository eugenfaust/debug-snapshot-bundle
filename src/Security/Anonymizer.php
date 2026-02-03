<?php

declare(strict_types=1);

namespace Evgenijfaustov\DebugSnapshotBundle\Security;

use Evgenijfaustov\DebugSnapshotBundle\Profile\Profile;
use RuntimeException;

final class Anonymizer
{
    public function anonymizeSnapshotArray(array $snapshotData, Profile $profile): array
    {
        if (($snapshotData['format'] ?? null) === null) {
            throw new RuntimeException('Invalid snapshot payload.');
        }

        $piiFields = $profile->getPiiFields();
        $entities = $snapshotData['entities'] ?? [];

        if (!is_array($entities)) {
            throw new RuntimeException('Invalid snapshot payload.');
        }

        foreach ($entities as $index => $entity) {
            $class = $entity['class'] ?? null;
            if (!is_string($class)) {
                continue;
            }

            $fieldsToMask = $piiFields[$class] ?? null;
            if ($fieldsToMask === null) {
                continue;
            }

            $fields = $entity['fields'] ?? [];
            if (!is_array($fields)) {
                $fields = [];
            }

            foreach ($fieldsToMask as $fieldToMask) {
                if (!array_key_exists($fieldToMask, $fields)) {
                    continue;
                }
                $fields[$fieldToMask] = $this->maskValue($fields[$fieldToMask]);
            }

            $entity['fields'] = $fields;
            $entities[$index] = $entity;
        }

        $snapshotData['entities'] = $entities;

        return $snapshotData;
    }

    private function maskValue(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            return '***';
        }

        if (is_int($value) || is_float($value)) {
            return 0;
        }

        if (is_bool($value)) {
            return false;
        }

        if (is_array($value)) {
            return [];
        }

        return '***';
    }
}
