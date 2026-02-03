<?php

declare(strict_types=1);

namespace Evgenijfaustov\DebugSnapshotBundle\Service;

use Evgenijfaustov\DebugSnapshotBundle\IO\SnapshotReader;
use Evgenijfaustov\DebugSnapshotBundle\Import\DoctrineSnapshotImporter;
use Evgenijfaustov\DebugSnapshotBundle\Profile\ProfileRegistry;
use Evgenijfaustov\DebugSnapshotBundle\Security\Anonymizer;
use RuntimeException;

final class SnapshotImporter
{
    private SnapshotReader $reader;
    private DoctrineSnapshotImporter $importer;
    private ProfileRegistry $profiles;
    private Anonymizer $anonymizer;

    public function __construct(
        SnapshotReader $reader,
        DoctrineSnapshotImporter $importer,
        ProfileRegistry $profiles,
        Anonymizer $anonymizer
    ) {
        $this->reader = $reader;
        $this->importer = $importer;
        $this->profiles = $profiles;
        $this->anonymizer = $anonymizer;
    }

    public function import(string $archivePath, bool $anonymize): void
    {
        $payload = $this->reader->read($archivePath);
        $snapshot = $payload['snapshot'];

        if ($anonymize) {
            $profileName = $payload['meta']['profile'] ?? null;
            if (!is_string($profileName)) {
                throw new RuntimeException('Snapshot profile is missing.');
            }

            $profile = $this->profiles->get($profileName);
            $snapshot = $this->anonymizer->anonymizeSnapshotArray($snapshot, $profile);
        }

        $this->importer->import($snapshot);
    }
}
