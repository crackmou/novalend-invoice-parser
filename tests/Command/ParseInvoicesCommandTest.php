<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\ParseInvoicesCommand;
use App\Reader\CsvInvoiceReader;
use App\Reader\InvoiceReaderRegistry;
use App\Reader\JsonInvoiceReader;
use App\Repository\InvoiceWriterInterface;
use App\Service\InvoiceParser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class ParseInvoicesCommandTest extends TestCase
{
    private string $dataDir;

    protected function setUp(): void
    {
        // Répertoire data/ isolé, avec quelques fichiers de tailles maîtrisées.
        $base = sys_get_temp_dir().'/parse-cmd-'.uniqid('', true);
        $this->dataDir = $base.'/data';
        mkdir($this->dataDir, 0777, true);

        file_put_contents(
            $this->dataDir.'/invoices.csv',
            "FAKE-1\t10.5\tEUR\tAlice\tOlinn\t2025-02-03\n",
        );
        file_put_contents(
            $this->dataDir.'/invoices.json',
            '[{"id_externe":"FAKE-2","montant":20,"devise":"EUR","nom":"Bob","partenaire":"Olinn"}]',
        );
        file_put_contents($this->dataDir.'/notes.txt', 'format non supporté');
    }

    protected function tearDown(): void
    {
        array_map('unlink', (array) glob($this->dataDir.'/*'));
        rmdir($this->dataDir);
        rmdir(\dirname($this->dataDir));
    }

    public function testWithoutOptionImportsEveryFile(): void
    {
        $writer = $this->createMock(InvoiceWriterInterface::class);
        // Les deux fichiers exploitables (csv + json) sont persistés ; le .txt échoue.
        $writer->expects(self::exactly(2))->method('upsertBatch');

        $tester = $this->runCommand($writer, []);

        $tester->assertCommandIsSuccessful();
        $output = $tester->getDisplay();
        self::assertStringContainsString('invoices.csv', $output);
        self::assertStringContainsString('invoices.json', $output);
        self::assertStringContainsString('notes.txt', $output);
        self::assertStringContainsString('Extension de fichier non supportée', $output);
    }

    public function testPathGlobFiltersFiles(): void
    {
        $writer = $this->createMock(InvoiceWriterInterface::class);
        $writer->expects(self::once())->method('upsertBatch');

        $tester = $this->runCommand($writer, ['--path' => $this->dataDir.'/*.csv']);

        $tester->assertCommandIsSuccessful();
        $output = $tester->getDisplay();
        self::assertStringContainsString('invoices.csv', $output);
        self::assertStringNotContainsString('invoices.json', $output);
    }

    public function testPathCanTargetASingleFile(): void
    {
        $writer = $this->createMock(InvoiceWriterInterface::class);
        $writer->expects(self::once())->method('upsertBatch');

        $tester = $this->runCommand($writer, ['--path' => $this->dataDir.'/invoices.json']);

        $tester->assertCommandIsSuccessful();
        $output = $tester->getDisplay();
        self::assertStringContainsString('invoices.json', $output);
        self::assertStringNotContainsString('invoices.csv', $output);
    }

    public function testPathMatchingNothingWarns(): void
    {
        $writer = $this->createMock(InvoiceWriterInterface::class);
        $writer->expects(self::never())->method('upsertBatch');

        $tester = $this->runCommand($writer, ['--path' => $this->dataDir.'/*.xml']);

        $tester->assertCommandIsSuccessful();
        self::assertStringContainsString('Aucun fichier à importer', $tester->getDisplay());
    }

    /**
     * @param array<string, string> $input
     */
    private function runCommand(InvoiceWriterInterface $writer, array $input): CommandTester
    {
        $registry = new InvoiceReaderRegistry([new CsvInvoiceReader(), new JsonInvoiceReader()]);
        $parser = new InvoiceParser($registry, $writer);
        // projectDir = le parent de data/ pour ce test.
        $command = new ParseInvoicesCommand($parser, \dirname($this->dataDir));

        $tester = new CommandTester($command);
        $tester->execute($input);

        return $tester;
    }
}
