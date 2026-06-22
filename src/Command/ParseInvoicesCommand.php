<?php

declare(strict_types=1);


namespace App\Command;

use App\Service\InvoiceParser;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
        $this->parser->parse('data/invoices.json');
        $this->parser->parse('data/invoices.csv');
        $this->parser->parse('data/invoices_1000k.csv');
        return Command::SUCCESS;
    }
}
