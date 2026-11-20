<?php

namespace App\Command;

use App\Exception\ImportException;
use App\Service\ExportIdentifiants;
use App\Service\ImportUser;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:import-users',
    description: 'Importe des étudiants et enseignants depuis un ou plusieurs fichiers CSV.'
)]
class ImportUsersCommand extends Command
{
    private const DEFAULT_DIRECTORY = '/import_csv';

    public function __construct(
        private readonly ImportUser $importUser,
        private readonly ExportIdentifiants $exportIdentifiants,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('path', 'p', InputOption::VALUE_REQUIRED, "Chemin d'un fichier CSV ou d'un répertoire de CSV (défaut : import_csv/ à la racine du projet)")
            ->addOption('export', null, InputOption::VALUE_REQUIRED, 'Fichier CSV où écrire les identifiants créés');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $path = $input->getOption('path') ?? $this->projectDir . self::DEFAULT_DIRECTORY;

        $files = is_dir($path) ? glob(rtrim($path, '/') . '/*.csv') : (is_file($path) ? [$path] : []);
        if (!$files) {
            $io->error(sprintf('Aucun fichier CSV trouvé : %s', $path));

            return Command::FAILURE;
        }

        $status = Command::SUCCESS;
        $comptes = [];
        foreach ($files as $file) {
            $io->section(basename($file));
            try {
                $this->importUser->importerCsv($file, $io->progressStart(...), $io->progressAdvance(...));
                $io->progressFinish();
            } catch (ImportException $e) {
                $this->afficherErreur($io, $e);
                $status = Command::FAILURE;
                continue;
            }
            $comptes = [...$comptes, ...$this->importUser->getComptesCrees()];
            $io->listing($this->importUser->getLignesComptesCrees());
            $io->success(sprintf('%d utilisateur(s) créé(s).', count($this->importUser->getComptesCrees())));
        }

        $export = $input->getOption('export');
        if (null !== $export && [] !== $comptes) {
            try {
                $this->exportIdentifiants->exporterVers($export, $comptes);
                $io->success(sprintf('Identifiants exportés dans %s', $export));
            } catch (ImportException $e) {
                $this->afficherErreur($io, $e);
                $status = Command::FAILURE;
            }
        }

        return $status;
    }

    private function afficherErreur(SymfonyStyle $io, ImportException $e): void
    {
        $io->error(sprintf('[%s] %s', $e->getType()->value, $e->getMessage()));
    }
}
