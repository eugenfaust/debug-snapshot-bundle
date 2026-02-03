<?php

declare(strict_types=1);

namespace Evgenijfaustov\DebugSnapshotBundle\Import;

use BackedEnum;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionNamedType;

final class ValueDenormalizer
{
    /**
     * @param array<string, array{factory: string}> $hydrateMap
     */
    public function __construct(private array $hydrateMap = [])
    {
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
                $reflection = new ReflectionMethod($targetClass, $method);

                return $targetClass::$method($this->coerceValueForMethod($reflection, $value));
            }
        }

        if ($this->hasPublicConstructor($targetClass)) {
            $reflection = new ReflectionClass($targetClass);
            $constructor = $reflection->getConstructor();

            return new $targetClass($this->coerceValueForMethod($constructor, $value));
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

        $reflectionMethod = new ReflectionMethod($class, $method);

        return $class::$method($this->coerceValueForMethod($reflectionMethod, $value));
    }

    private function hasPublicStatic(string $class, string $method): bool
    {
        if (!method_exists($class, $method)) {
            return false;
        }

        $reflectionMethod = new ReflectionMethod($class, $method);

        return $reflectionMethod->isPublic() && $reflectionMethod->isStatic();
    }

    private function hasPublicConstructor(string $class): bool
    {
        $reflectionClass = new ReflectionClass($class);
        $constructor = $reflectionClass->getConstructor();

        if ($constructor === null || !$constructor->isPublic()) {
            return false;
        }

        $parameters = $constructor->getParameters();

        return count($parameters) === 1;
    }

    private function coerceValueForMethod(?ReflectionFunctionAbstract $reflectionFunctionAbstract, mixed $value): mixed
    {
        if (!$reflectionFunctionAbstract instanceof \ReflectionFunctionAbstract || $value === null) {
            return $value;
        }

        $parameters = $reflectionFunctionAbstract->getParameters();

        if (count($parameters) !== 1) {
            return $value;
        }

        $type = $parameters[0]->getType();

        if ($type === null || !$type instanceof ReflectionNamedType) {
            return $value;
        }

        $typeName = $type->getName();

        if (!is_a($typeName, DateTimeInterface::class, true)) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return $value;
        }

        return new DateTimeImmutable((string) $value);
    }
}
