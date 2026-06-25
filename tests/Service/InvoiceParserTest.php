<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Dto\InvoiceInput;
use App\Entity\Invoice;
use App\Entity\Partner;
use App\Enum\Currency;
use App\Reader\InvoiceReaderInterface;
use App\Reader\InvoiceReaderRegistry;
use App\Repository\InvoiceRepository;
use App\Repository\PartnerRepository;
use App\Service\InvoiceParser;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class InvoiceParserTest extends TestCase
{
    public function testEachInvoiceIsPersistedThenFlushedOnce(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::exactly(3))->method('persist')->with(self::isInstanceOf(Invoice::class));
        $em->expects(self::once())->method('flush');

        $parser = $this->makeParser(
            $this->readerYielding(3),
            $em,
            $this->partnerRepositoryReturning($this->partner('Olinn')),
            $this->invoiceRepositoryFinding(null),
        );

        $parser->parse('data/invoices.csv');
    }

    public function testExistingInvoiceIsUpdatedInPlace(): void
    {
        $existing = new Invoice();
        $existing->name = 'Ancien nom';
        $existing->amount = 1.0;
        $existing->currency = Currency::JPY;

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')->with(self::identicalTo($existing));
        $em->expects(self::once())->method('flush');

        $parser = $this->makeParser(
            $this->readerYielding(1),
            $em,
            $this->partnerRepositoryReturning($this->partner('Olinn')),
            $this->invoiceRepositoryFinding($existing),
        );

        $parser->parse('data/invoices.csv');

        // L'entité existante est mise à jour, pas remplacée.
        self::assertSame('Name', $existing->name);
        self::assertSame(10.0, $existing->amount);
        self::assertSame(Currency::EUR, $existing->currency);
        self::assertSame('INV-0', $existing->idExternal);
    }

    public function testPartnerIsResolvedOncePerName(): void
    {
        $partnerRepository = $this->getMockBuilder(PartnerRepository::class)
            ->disableOriginalConstructor()
            ->addMethods(['findOneByName'])
            ->getMock();
        $partnerRepository->expects(self::once())
            ->method('findOneByName')
            ->with('Olinn')
            ->willReturn($this->partner('Olinn'));

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::exactly(2))->method('persist');
        $em->expects(self::once())->method('flush');

        $parser = $this->makeParser(
            $this->readerYielding(2),
            $em,
            $partnerRepository,
            $this->invoiceRepositoryFinding(null),
        );

        $parser->parse('data/invoices.csv');
    }

    public function testUnknownPartnerThrows(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('flush');

        $parser = $this->makeParser(
            $this->readerYielding(1),
            $em,
            $this->partnerRepositoryReturning(null),
            $this->invoiceRepositoryFinding(null),
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Partenaire introuvable');

        $parser->parse('data/invoices.csv');
    }

    public function testMissingFileThrows(): void
    {
        $parser = $this->makeParser(
            $this->readerYielding(0),
            $this->createMock(EntityManagerInterface::class),
            $this->partnerRepositoryReturning(null),
            $this->invoiceRepositoryFinding(null),
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Fichier introuvable');

        $parser->parse('data/does_not_exist.csv');
    }

    private function makeParser(
        InvoiceReaderInterface $reader,
        EntityManagerInterface $em,
        PartnerRepository $partnerRepository,
        InvoiceRepository $invoiceRepository,
    ): InvoiceParser {
        return new InvoiceParser(
            new InvoiceReaderRegistry([$reader]),
            $em,
            $partnerRepository,
            $invoiceRepository,
        );
    }

    private function readerYielding(int $count, string $partnerName = 'Olinn'): InvoiceReaderInterface
    {
        return new class($count, $partnerName) implements InvoiceReaderInterface {
            public function __construct(private readonly int $count, private readonly string $partnerName)
            {
            }

            public function supports(string $extension): bool
            {
                return true;
            }

            public function read(string $filePath): iterable
            {
                for ($i = 0; $i < $this->count; ++$i) {
                    yield new InvoiceInput(sprintf('INV-%d', $i), 'Name', 10.0, Currency::EUR, $this->partnerName);
                }
            }
        };
    }

    private function partner(string $name): Partner
    {
        $partner = new Partner();
        $partner->name = $name;

        return $partner;
    }

    private function partnerRepositoryReturning(?Partner $partner): PartnerRepository
    {
        $repository = $this->getMockBuilder(PartnerRepository::class)
            ->disableOriginalConstructor()
            ->addMethods(['findOneByName'])
            ->getMock();
        $repository->method('findOneByName')->willReturn($partner);

        return $repository;
    }

    private function invoiceRepositoryFinding(?Invoice $invoice): InvoiceRepository
    {
        $repository = $this->createMock(InvoiceRepository::class);
        $repository->method('findOneBy')->willReturn($invoice);

        return $repository;
    }
}
