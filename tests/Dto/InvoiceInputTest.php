<?php

declare(strict_types=1);

namespace App\Tests\Dto;

use App\Dto\InvoiceInput;
use App\Enum\Currency;
use App\Exception\InvalidInvoiceDataException;
use PHPUnit\Framework\TestCase;

final class InvoiceInputTest extends TestCase
{
    public function testFromRawBuildsAValidInvoice(): void
    {
        $invoice = InvoiceInput::fromRaw('FAKE-INV-0001', 'Frank Green', '670.43', 'EUR', 'Olinn');

        self::assertSame('FAKE-INV-0001', $invoice->idExternal);
        self::assertSame('Frank Green', $invoice->name);
        self::assertSame(670.43, $invoice->amount);
        self::assertSame(Currency::EUR, $invoice->currency);
        self::assertSame('Olinn', $invoice->partnerName);
    }

    public function testUnsupportedCurrencyIsRejected(): void
    {
        $this->expectException(InvalidInvoiceDataException::class);
        $this->expectExceptionMessage('Devise non supportée "BTC"');

        InvoiceInput::fromRaw('FAKE-INV-0001', 'Frank Green', '10', 'BTC', 'Olinn');
    }

    public function testNonNumericAmountIsRejected(): void
    {
        $this->expectException(InvalidInvoiceDataException::class);

        InvoiceInput::fromRaw('FAKE-INV-0001', 'Frank Green', 'N/A', 'EUR', 'Olinn');
    }

    public function testNegativeAmountIsRejected(): void
    {
        $this->expectException(InvalidInvoiceDataException::class);

        InvoiceInput::fromRaw('FAKE-INV-0001', 'Frank Green', '-5', 'EUR', 'Olinn');
    }

    public function testMissingPartnerIsRejected(): void
    {
        $this->expectException(InvalidInvoiceDataException::class);

        InvoiceInput::fromRaw('FAKE-INV-0001', 'Frank Green', '10', 'EUR', '   ');
    }
}
