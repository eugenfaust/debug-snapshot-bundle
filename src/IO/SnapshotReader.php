<?php

declare(strict_types=1);

namespace Evgenijfaustov\DebugSnapshotBundle\IO;

use Evgenijfaustov\DebugSnapshotBundle\Snapshot\Snapshot;
use RuntimeException;
use ZipArchive;

final class SnapshotReader
{
    public function read(string $archivePath): array
    {
        if (!is_file($archivePath)) {
            throw new RuntimeException('Snapshot archive not found.');
        }

        $zipArchive = new ZipArchive();
        if ($zipArchive->open($archivePath) !== true) {
            throw new RuntimeException('Failed to open snapshot archive.');
        }

        $metaRaw = $zipArchive->getFromName('meta.json');
        $snapshotRaw = $zipArchive->getFromName('snapshot.json');
        $zipArchive->close();

        if ($metaRaw === false || $snapshotRaw === false) {
            throw new RuntimeException('Snapshot archive is missing required files.');
        }

        $meta = json_decode($metaRaw, true, 512, JSON_THROW_ON_ERROR);
        $snapshot = json_decode($snapshotRaw, true, 512, JSON_THROW_ON_ERROR);

        if (($meta['format'] ?? null) !== Snapshot::FORMAT) {
            throw new RuntimeException('Unsupported snapshot format.');
        }

        if (($snapshot['format'] ?? null) !== Snapshot::FORMAT) {
            throw new RuntimeException('Unsupported snapshot format.');
        }

        $checksum = hash('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
        if (!isset($meta['checksum']) || $meta['checksum'] !== $checksum) {
            throw new RuntimeException('Snapshot checksum mismatch.');
        }

        return [
            'meta' => $meta,
            'snapshot' => $snapshot,
        ];
    }
}
