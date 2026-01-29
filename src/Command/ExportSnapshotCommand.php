<?php

declare(strict_types=1);

namespace Evgenijfaustov\DebugSnapshotBundle\Command;

use Evgenijfaustov\DebugSnapshotBundle\Export\DoctrineGraphWalker;
use Evgenijfaustov\DebugSnapshotBundle\IO\SnapshotArchiver;
use Evgenijfaustov\DebugSnapshotBundle\Profile\ProfileRegistry;
use Evgenijfaustov\DebugSnapshotBundle\Security\Anonymizer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'debug:snapshot:export',
    description: 'Export debug snapshot for a configured profile.'
)]
final class ExportSnapshotCommand extends Command
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
        parent::__construct();

        $this->profiles = $profiles;
        $this->walker = $walker;
        $this->archiver = $archiver;
        $this->anonymizer = $anonymizer;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('profile', InputArgument::REQUIRED, 'Profile name')
            ->addArgument('id', InputArgument::REQUIRED, 'Root entity id')
            ->addOption('out', null, InputOption::VALUE_REQUIRED, 'Output directory', 'var/snapshots')
            ->addOption('anonymize', null, InputOption::VALUE_REQUIRED, 'Mask PII fields', '0');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $profileName = (string) $input->getArgument('profile');
        $id = $input->getArgument('id');
        $outputDir = (string) $input->getOption('out');
        $anonymize = filter_var($input->getOption('anonymize'), FILTER_VALIDATE_BOOL);

        $profile = $this->profiles->get($profileName);
        $snapshot = $this->walker->walk($profile, is_numeric($id) ? (int) $id : (string) $id);
        $snapshotData = $snapshot->toArray();

        if ($anonymize) {
            $snapshotData = $this->anonymizer->anonymizeSnapshotArray($snapshotData, $profile);
        }

        $archivePath = $this->archiver->archive($snapshotData, $profileName, $outputDir);

        $output->writeln($archivePath);

        return Command::SUCCESS;
    }
}
