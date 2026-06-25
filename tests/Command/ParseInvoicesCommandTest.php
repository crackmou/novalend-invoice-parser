<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\ParseInvoicesCommand;
use App\Entity\Partner;
use App\Reader\CsvInvoiceReader;
use App\Reader\InvoiceReaderRegistry;
use App\Reader\JsonInvoiceReader;
use App\Repository\InvoiceRepository;
use App\Repository\PartnerRepository;
use App\Service\InvoiceParser;
use Doctrine\ORM\EntityManagerInterface;
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
        $tester = $this->runCommand([]);

        $tester->assertCommandIsSuccessful();
        $output = $tester->getDisplay();
        self::assertStringContainsString('invoices.csv', $output);
        self::assertStringContainsString('invoices.json', $output);
        self::assertStringContainsString('notes.txt', $output);
        // Les deux fichiers exploitables sont importés ; le .txt échoue.
        self::assertSame(2, substr_count($output, 'Import OK'));
        self::assertStringContainsString('Extension de fichier non supportée', $output);
    }

    public function testPathGlobFiltersFiles(): void
    {
        $tester = $this->runCommand(['--path' => $this->dataDir.'/*.csv']);

        $tester->assertCommandIsSuccessful();
        $output = $tester->getDisplay();
        self::assertStringContainsString('invoices.csv', $output);
        self::assertStringNotContainsString('invoices.json', $output);
        self::assertSame(1, substr_count($output, 'Import OK'));
    }

    public function testPathCanTargetASingleFile(): void
    {
        $tester = $this->runCommand(['--path' => $this->dataDir.'/invoices.json']);

        $tester->assertCommandIsSuccessful();
        $output = $tester->getDisplay();
        self::assertStringContainsString('invoices.json', $output);
        self::assertStringNotContainsString('invoices.csv', $output);
        self::assertSame(1, substr_count($output, 'Import OK'));
    }

    public function testPathMatchingNothingWarns(): void
    {
        $tester = $this->runCommand(['--path' => $this->dataDir.'/*.xml']);

        $tester->assertCommandIsSuccessful();
        self::assertStringContainsString('Aucun fichier à importer', $tester->getDisplay());
    }

    /**
     * @param array<string, string> $input
     */
    private function runCommand(array $input): CommandTester
    {
        // projectDir = le parent de data/ pour ce test.
        $command = new ParseInvoicesCommand($this->makeParser(), \dirname($this->dataDir));

        $tester = new CommandTester($command);
        $tester->execute($input);

        return $tester;
    }

    /**
     * Parser réel câblé sur des dépendances mockées : le partenaire « Olinn »
     * des fixtures est résolu et la persistance est neutralisée (pas de base).
     */
    private function makeParser(): InvoiceParser
    {
        $registry = new InvoiceReaderRegistry([new CsvInvoiceReader(), new JsonInvoiceReader()]);

        $partner = new Partner();
        $partner->name = 'Olinn';

        $partnerRepository = $this->getMockBuilder(PartnerRepository::class)
            ->disableOriginalConstructor()
            ->addMethods(['findOneByName'])
            ->getMock();
        $partnerRepository->method('findOneByName')->willReturn($partner);

        $invoiceRepository = $this->createMock(InvoiceRepository::class);
        $invoiceRepository->method('findOneBy')->willReturn(null);

        return new InvoiceParser(
            $registry,
            $this->createMock(EntityManagerInterface::class),
            $partnerRepository,
            $invoiceRepository,
        );
    }
}
