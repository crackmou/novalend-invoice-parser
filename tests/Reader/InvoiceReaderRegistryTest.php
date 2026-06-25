<?php

declare(strict_types=1);

namespace App\Tests\Reader;

use App\Exception\UnsupportedFileFormatException;
use App\Reader\CsvInvoiceReader;
use App\Reader\InvoiceReaderRegistry;
use App\Reader\JsonInvoiceReader;
use PHPUnit\Framework\TestCase;

final class InvoiceReaderRegistryTest extends TestCase
{
    private InvoiceReaderRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new InvoiceReaderRegistry([new CsvInvoiceReader(), new JsonInvoiceReader()]);
    }

    public function testSelectsCsvReader(): void
    {
        self::assertInstanceOf(CsvInvoiceReader::class, $this->registry->readerFor('data/invoices.csv'));
    }

    public function testSelectsJsonReader(): void
    {
        self::assertInstanceOf(JsonInvoiceReader::class, $this->registry->readerFor('data/invoices.json'));
    }

    public function testExtensionIsCaseInsensitive(): void
    {
        self::assertInstanceOf(JsonInvoiceReader::class, $this->registry->readerFor('data/INVOICES.JSON'));
    }

    public function testUnsupportedFormatThrows(): void
    {
        $this->expectException(UnsupportedFileFormatException::class);
        $this->expectExceptionMessage('xml');

        $this->registry->readerFor('data/invoices.xml');
    }
}
