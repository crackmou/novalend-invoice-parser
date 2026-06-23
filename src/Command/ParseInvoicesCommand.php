<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\InvoiceParser;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:parse')]
class ParseInvoicesCommand extends Command
{
    private InvoiceParser $parser;

    public function __construct(InvoiceParser $parser)
    {
        parent::__construct();
        $this->parser = $parser;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $files = [
            'data/invoices.json',
            'data/invoices_invalid.json',
            'data/invoices_missing_column.json',
            'data/invoices.xml',
            'data/invoices.csv',
            'data/missingfile.csv',
            'data/invoices_1000k.csv',
        ];
        foreach ($files as $file) {
            try {
                $io->info(sprintf('Trying to import file %s', $file));
                $this->parser->parse($file);
                $io->info('Import OK');
            } catch (\Exception $e) {
                $io->error($e->getMessage());
            }
        }

        return Command::SUCCESS;
    }
}
