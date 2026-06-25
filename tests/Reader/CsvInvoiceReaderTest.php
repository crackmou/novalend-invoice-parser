<?php

declare(strict_types=1);

namespace App\Tests\Reader;

use App\Dto\InvoiceInput;
use App\Enum\Currency;
use App\Reader\CsvInvoiceReader;
use PHPUnit\Framework\TestCase;

final class CsvInvoiceReaderTest extends TestCase
{
    private CsvInvoiceReader $reader;

    protected function setUp(): void
    {
        $this->reader = new CsvInvoiceReader();
    }

    public function testSupportsOnlyCsv(): void
    {
        self::assertTrue($this->reader->supports('csv'));
        self::assertFalse($this->reader->supports('json'));
    }

    public function testReadsAllInvoicesAsTypedDtos(): void
    {
        $invoices = iterator_to_array($this->reader->read('data/invoices.csv'));

        self::assertCount(10, $invoices);
        self::assertContainsOnlyInstancesOf(InvoiceInput::class, $invoices);

        $first = $invoices[0];
        self::assertSame('FAKE-INV-0001', $first->idExternal);
        self::assertSame('Frank Green', $first->name);
        self::assertSame(670.43, $first->amount);
        self::assertSame(Currency::EUR, $first->currency);
        self::assertSame('Olinn', $first->partnerName);
    }
}
