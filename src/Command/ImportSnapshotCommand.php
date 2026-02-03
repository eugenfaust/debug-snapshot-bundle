<?php

declare(strict_types=1);

namespace Evgenijfaustov\DebugSnapshotBundle\Command;

use Evgenijfaustov\DebugSnapshotBundle\Service\SnapshotImporter;
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
    public function __construct(
        private readonly SnapshotImporter $importer
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('archive', InputArgument::REQUIRED, 'Path to snapshot zip')
            ->addOption('anonymize', null, InputOption::VALUE_REQUIRED, 'Mask PII fields', '0')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $archive = (string) $input->getArgument('archive');
        $anonymize = filter_var($input->getOption('anonymize'), FILTER_VALIDATE_BOOL);

        $this->importer->import($archive, $anonymize);

        $output->writeln('OK');

        return Command::SUCCESS;
    }
}
