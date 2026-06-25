<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\InvoiceParser;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:parse',
    description: 'Importe les fichiers de factures du répertoire data/, éventuellement filtrés par une expression régulière.',
)]
class ParseInvoicesCommand extends Command
{
    private const DATA_DIR = 'data';

    public function __construct(
        private readonly InvoiceParser $parser,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'path',
                'c',
                InputOption::VALUE_OPTIONAL,
                'Expression régulière filtrant les chemins à importer (ex: "\.csv$"). Si absent, tous les fichiers de data/ sont importés.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $rawPath = $input->getOption('path');
        $path = is_string($rawPath) ? $rawPath : null;

        try {
            $files = $this->resolveFiles($path);
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            $io->error($e->getMessage());

            return Command::INVALID;
        }

        if ([] === $files) {
            $io->warning('Aucun fichier à importer.');

            return Command::SUCCESS;
        }

        foreach ($files as $file) {
            try {
                $io->info(sprintf('Trying to import file %s', $file));
                $this->parser->parse($file);
                $io->info('Import OK');
            } catch (\Throwable $e) {
                $io->error($e->getMessage());
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Liste les fichiers de data/, filtrés par la regex si elle est fournie.
     *
     * @return list<string>
     */
    private function resolveFiles(?string $path): array
    {
        $paths = glob($path ?? $this->projectDir.\DIRECTORY_SEPARATOR.self::DATA_DIR.\DIRECTORY_SEPARATOR.'*');
        $files = array_filter(false === $paths ? [] : $paths, 'is_file');
        return $files;
    }
}
