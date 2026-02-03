<?php

declare(strict_types=1);

namespace Evgenijfaustov\DebugSnapshotBundle\Service;

use Evgenijfaustov\DebugSnapshotBundle\Export\DoctrineGraphWalker;
use Evgenijfaustov\DebugSnapshotBundle\IO\SnapshotArchiver;
use Evgenijfaustov\DebugSnapshotBundle\Profile\ProfileRegistry;
use Evgenijfaustov\DebugSnapshotBundle\Security\Anonymizer;

final class SnapshotExporter
{
    private ProfileRegistry $profiles;
    private DoctrineGraphWalker $walker;
    private SnapshotArchiver $archiver;
    private Anonymizer $anonymizer;

    public function __construct(
        ProfileRegistry $profiles,
        DoctrineGraphWalker $walker,
        SnapshotArchiver $archiver,
        Anonymizer $anonymizer
    ) {
        $this->profiles = $profiles;
        $this->walker = $walker;
        $this->archiver = $archiver;
        $this->anonymizer = $anonymizer;
    }

    public function export(string $profileName, int|string $id, string $outputDir, bool $anonymize): string
    {
        $profile = $this->profiles->get($profileName);
        $snapshot = $this->walker->walk($profile, is_numeric($id) ? (int) $id : (string) $id);
        $snapshotData = $snapshot->toArray();

        if ($anonymize) {
            $snapshotData = $this->anonymizer->anonymizeSnapshotArray($snapshotData, $profile);
        }

        return $this->archiver->archive($snapshotData, $profileName, $outputDir);
    }
}
