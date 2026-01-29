<?php

declare(strict_types=1);

namespace Evgenijfaustov\DebugSnapshotBundle\Export;

use BackedEnum;
use DateTimeInterface;
use InvalidArgumentException;
use UnitEnum;

final class ScalarNormalizer
{
    public function normalize(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_scalar($value)) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format(DateTimeInterface::ATOM);
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof UnitEnum) {
            return $value->name;
        }

        if (is_array($value)) {
            $normalized = [];
            foreach ($value as $key => $item) {
                $normalized[$key] = $this->normalize($item);
            }
            return $normalized;
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return (string) $value;
        }

        throw new InvalidArgumentException(sprintf('Unsupported scalar value of type "%s".', get_debug_type($value)));
    }
}
