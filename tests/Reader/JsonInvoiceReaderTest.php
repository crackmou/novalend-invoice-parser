<?php

declare(strict_types=1);

namespace App\Tests\Reader;

use App\Dto\InvoiceInput;
use App\Enum\Currency;
use App\Exception\InvalidInvoiceDataException;
use App\Reader\JsonInvoiceReader;
use PHPUnit\Framework\TestCase;

final class JsonInvoiceReaderTest extends TestCase
{
    private JsonInvoiceReader $reader;

    protected function setUp(): void
    {
        $this->reader = new JsonInvoiceReader();
    }

    public function testSupportsOnlyJson(): void
    {
        self::assertTrue($this->reader->supports('json'));
        self::assertFalse($this->reader->supports('csv'));
    }

    public function testReadsAllInvoicesAsTypedDtos(): void
    {
        $invoices = iterator_to_array($this->reader->read('data/invoices.json'));

        self::assertCount(10, $invoices);
        self::assertContainsOnlyInstancesOf(InvoiceInput::class, $invoices);

        $first = $invoices[0];
        self::assertSame('FAKE-INV-0001', $first->idExternal);
        self::assertSame(670.43, $first->amount);
        self::assertSame(Currency::EUR, $first->currency);
        self::assertSame('La banque postale', $first->partnerName);
    }

    public function testInvalidJsonThrowsDomainException(): void
    {
        $this->expectException(InvalidInvoiceDataException::class);

        iterator_to_array($this->reader->read('data/invoices_invalid.json'));
    }

    public function testMissingColumnThrowsDomainException(): void
    {
        $this->expectException(InvalidInvoiceDataException::class);
        $this->expectExceptionMessage('"nom"');

        iterator_to_array($this->reader->read('data/invoices_missing_column.json'));
    }
}
