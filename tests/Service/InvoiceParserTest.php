<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Dto\InvoiceInput;
use App\Enum\Currency;
use App\Reader\InvoiceReaderInterface;
use App\Reader\InvoiceReaderRegistry;
use App\Repository\InvoiceWriterInterface;
use App\Service\InvoiceParser;
use PHPUnit\Framework\TestCase;

final class InvoiceParserTest extends TestCase
{
    public function testInvoicesAreUpsertedInBatches(): void
    {
        // 2 500 factures => lots attendus de 1000, 1000, 500.
        $reader = $this->readerYielding(2500);
        $registry = new InvoiceReaderRegistry([$reader]);

        $batchSizes = [];
        $repository = $this->createMock(InvoiceWriterInterface::class);
        $repository->expects(self::exactly(3))
            ->method('upsertBatch')
            ->willReturnCallback(static function (array $batch) use (&$batchSizes): void {
                $batchSizes[] = \count($batch);
            });

        // On vise un fichier réel pour passer le contrôle d'existence ;
        // le reader factice ignore son contenu.
        (new InvoiceParser($registry, $repository))->parse('data/invoices.csv');

        self::assertSame([1000, 1000, 500], $batchSizes);
    }

    public function testNothingIsPersistedForAnEmptyFile(): void
    {
        $registry = new InvoiceReaderRegistry([$this->readerYielding(0)]);

        $repository = $this->createMock(InvoiceWriterInterface::class);
        $repository->expects(self::never())->method('upsertBatch');

        (new InvoiceParser($registry, $repository))->parse('data/invoices.csv');
    }

    public function testMissingFileThrows(): void
    {
        $registry = new InvoiceReaderRegistry([$this->readerYielding(0)]);
        $repository = $this->createMock(InvoiceWriterInterface::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Fichier introuvable');

        (new InvoiceParser($registry, $repository))->parse('data/does_not_exist.csv');
    }

    private function readerYielding(int $count): InvoiceReaderInterface
    {
        return new class($count) implements InvoiceReaderInterface {
            public function __construct(private readonly int $count)
            {
            }

            public function supports(string $extension): bool
            {
                return true;
            }

            public function read(string $filePath): iterable
            {
                for ($i = 0; $i < $this->count; ++$i) {
                    yield new InvoiceInput(sprintf('INV-%d', $i), 'Name', 10.0, Currency::EUR, 'Olinn');
                }
            }
        };
    }
}
