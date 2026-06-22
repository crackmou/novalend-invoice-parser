<?php

declare(strict_types=1);

namespace App\Tests;

use App\Repository\InvoiceRepository;
use App\Service\InvoiceParser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class InvoiceParserTest extends KernelTestCase
{
    public function testParseJson(): void
    {
        $invoiceRepository = $this->createMock(InvoiceRepository::class);
        $invoiceRepository->expects($this->exactly(10))->method('upsert');

        $invoiceParser = new InvoiceParser($invoiceRepository);

        $invoiceParser->parse('data/invoices.json');
    }

    public function testParseCsv(): void
    {
        $invoiceRepository = $this->createMock(InvoiceRepository::class);
        $invoiceRepository->expects($this->exactly(10))->method('upsert');

        $invoiceParser = new InvoiceParser($invoiceRepository);

        $invoiceParser->parse('data/invoices.csv');
    }
}
