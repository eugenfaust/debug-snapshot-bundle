<?php

declare(strict_types=1);

namespace Evgenijfaustov\DebugSnapshotBundle\Command;

use Evgenijfaustov\DebugSnapshotBundle\Service\SnapshotExporter;
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
    public function __construct(
        private readonly SnapshotExporter $exporter
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('profile', InputArgument::REQUIRED, 'Profile name')
            ->addArgument('id', InputArgument::REQUIRED, 'Root entity id')
            ->addOption('out', null, InputOption::VALUE_REQUIRED, 'Output directory', 'var/snapshots')
            ->addOption('anonymize', null, InputOption::VALUE_REQUIRED, 'Mask PII fields', '0')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $profileName = (string) $input->getArgument('profile');
        $id = $input->getArgument('id');
        $outputDir = (string) $input->getOption('out');
        $anonymize = filter_var($input->getOption('anonymize'), FILTER_VALIDATE_BOOL);

        $archivePath = $this->exporter->export($profileName, $id, $outputDir, $anonymize);

        $output->writeln($archivePath);

        return Command::SUCCESS;
    }
}
