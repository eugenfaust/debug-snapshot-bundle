<?php

declare(strict_types=1);

namespace Evgenijfaustov\DebugSnapshotBundle\IO;

use DateTimeImmutable;
use DateTimeInterface;
use Evgenijfaustov\DebugSnapshotBundle\Snapshot\Snapshot;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use ZipArchive;

final class SnapshotArchiver
{
    private Filesystem $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function archive(array $snapshotData, string $profile, string $outputDir): string
    {
        if ($outputDir === '') {
            throw new RuntimeException('Output directory is required.');
        }

        if (($snapshotData['format'] ?? null) !== Snapshot::FORMAT) {
            throw new RuntimeException('Unsupported snapshot format.');
        }

        $root = $snapshotData['root'] ?? null;
        if (!is_array($root) || !isset($root['id'])) {
            throw new RuntimeException('Snapshot root is missing.');
        }

        $this->filesystem->mkdir($outputDir);

        $tempDir = rtrim($outputDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '.debug-snapshot-' . uniqid('', true);
        $this->filesystem->mkdir($tempDir);

        $snapshotJson = json_encode($snapshotData, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $checksum = hash('sha256', $snapshotJson);

        $meta = [
            'format' => Snapshot::FORMAT,
            'createdAt' => (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
            'profile' => $profile,
            'root' => $root,
            'checksum' => $checksum,
        ];

        $metaJson = json_encode($meta, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        $this->filesystem->dumpFile($tempDir . DIRECTORY_SEPARATOR . 'snapshot.json', $snapshotJson);
        $this->filesystem->dumpFile($tempDir . DIRECTORY_SEPARATOR . 'meta.json', $metaJson);

        $fileName = sprintf(
            'debug-snapshot-%s-%s-%s.zip',
            $profile,
            $root['id'],
            (new DateTimeImmutable())->format('YmdHis')
        );

        $archivePath = rtrim($outputDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName;

        $zip = new ZipArchive();
        if ($zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->filesystem->remove($tempDir);
            throw new RuntimeException('Failed to create snapshot archive.');
        }

        $zip->addFile($tempDir . DIRECTORY_SEPARATOR . 'snapshot.json', 'snapshot.json');
        $zip->addFile($tempDir . DIRECTORY_SEPARATOR . 'meta.json', 'meta.json');
        $zip->close();

        $this->filesystem->remove($tempDir);

        return $archivePath;
    }
}
