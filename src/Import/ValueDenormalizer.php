<?php

declare(strict_types=1);

namespace Evgenijfaustov\DebugSnapshotBundle\Import;

use BackedEnum;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionMethod;

final class ValueDenormalizer
{
    /** @var array<string, array{factory: string}> */
    private array $hydrateMap;

    /** @param array<string, array{factory: string}> $hydrateMap */
    public function __construct(array $hydrateMap = [])
    {
        $this->hydrateMap = $hydrateMap;
    }

    public function denormalize(string $targetClass, mixed $value): mixed
    {
        if (isset($this->hydrateMap[$targetClass]['factory'])) {
            return $this->callFactory($targetClass, $this->hydrateMap[$targetClass]['factory'], $value);
        }

        if ($value === null) {
            if ($this->hasPublicStatic($targetClass, 'create')) {
                return $targetClass::create(null);
            }

            return null;
        }

        if (enum_exists($targetClass) && is_subclass_of($targetClass, BackedEnum::class)) {
            return $targetClass::from($value);
        }

        if (is_a($targetClass, DateTimeInterface::class, true)) {
            return new DateTimeImmutable((string) $value);
        }

        foreach (['fromString', 'create', 'from'] as $method) {
            if ($this->hasPublicStatic($targetClass, $method)) {
                return $targetClass::$method($value);
            }
        }

        if ($this->hasPublicConstructor($targetClass)) {
            return new $targetClass($value);
        }

        throw new InvalidArgumentException(sprintf(
            'No denormalizer available for %s. Add hydrate mapping or factory.',
            $targetClass
        ));
    }

    private function callFactory(string $class, string $method, mixed $value): mixed
    {
        if (!$this->hasPublicStatic($class, $method)) {
            throw new InvalidArgumentException(sprintf(
                'Hydrate factory %s::%s is not available or not public static.',
                $class,
                $method
            ));
        }

        return $class::$method($value);
    }

    private function hasPublicStatic(string $class, string $method): bool
    {
        if (!method_exists($class, $method)) {
            return false;
        }

        $reflection = new ReflectionMethod($class, $method);

        return $reflection->isPublic() && $reflection->isStatic();
    }

    private function hasPublicConstructor(string $class): bool
    {
        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        if ($constructor === null || !$constructor->isPublic()) {
            return false;
        }

        $parameters = $constructor->getParameters();

        return count($parameters) === 1;
    }
}
