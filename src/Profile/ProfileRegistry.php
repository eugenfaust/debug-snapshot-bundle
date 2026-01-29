<?php

declare(strict_types=1);

namespace Evgenijfaustov\DebugSnapshotBundle\Profile;

use InvalidArgumentException;

final class ProfileRegistry
{
    private array $profiles;

    public function __construct(array $profiles)
    {
        foreach ($profiles as $name => $profile) {
            if (!$profile instanceof Profile) {
                throw new InvalidArgumentException('ProfileRegistry expects Profile instances.');
            }
            if ($profile->getName() !== (string) $name) {
                throw new InvalidArgumentException(sprintf('Profile name mismatch for "%s".', (string) $name));
            }
        }

        $this->profiles = $profiles;
    }

    public function get(string $name): Profile
    {
        if (!isset($this->profiles[$name])) {
            throw new InvalidArgumentException(sprintf('Profile "%s" not found.', $name));
        }

        return $this->profiles[$name];
    }

    public function has(string $name): bool
    {
        return isset($this->profiles[$name]);
    }

    public function all(): array
    {
        return $this->profiles;
    }
}
