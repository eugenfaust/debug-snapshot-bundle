<?php

declare(strict_types=1);

namespace Evgenijfaustov\DebugSnapshotBundle\Command;

use Evgenijfaustov\DebugSnapshotBundle\IO\SnapshotReader;
use Evgenijfaustov\DebugSnapshotBundle\Import\DoctrineSnapshotImporter;
use Evgenijfaustov\DebugSnapshotBundle\Profile\ProfileRegistry;
use Evgenijfaustov\DebugSnapshotBundle\Security\Anonymizer;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'debug:snapshot:import',
    description: 'Import debug snapshot from archive.'
)]
final class ImportSnapshotCommand extends Command
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
        parent::__construct();

        $this->reader = $reader;
        $this->importer = $importer;
        $this->profiles = $profiles;
        $this->anonymizer = $anonymizer;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('archive', InputArgument::REQUIRED, 'Path to snapshot zip')
            ->addOption('anonymize', null, InputOption::VALUE_REQUIRED, 'Mask PII fields', '0');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $archive = (string) $input->getArgument('archive');
        $anonymize = filter_var($input->getOption('anonymize'), FILTER_VALIDATE_BOOL);

        $payload = $this->reader->read($archive);
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

        $output->writeln('OK');

        return Command::SUCCESS;
    }
}
