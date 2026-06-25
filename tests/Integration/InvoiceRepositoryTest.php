<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Dto\InvoiceInput;
use App\Entity\Partner;
use App\Enum\Currency;
use App\Repository\InvoiceRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Test d'intégration de l'upsert SQL natif : il s'exécute contre une vraie base
 * PostgreSQL et valide le comportement réellement délégué à la base
 * (clause ON CONFLICT, séquence, jointure partenaire).
 *
 * Chaque test tourne dans une transaction annulée en fin de test : aucune
 * donnée n'est laissée derrière lui.
 */
final class InvoiceRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private Connection $connection;
    private InvoiceRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $this->em = $container->get(EntityManagerInterface::class);
        $this->repository = $container->get(InvoiceRepository::class);
        $this->connection = $this->em->getConnection();

        try {
            $this->connection->executeQuery('SELECT 1');
        } catch (DbalException $e) {
            self::markTestSkipped('Base de données indisponible : '.$e->getMessage());
        }

        $this->connection->beginTransaction();
    }

    protected function tearDown(): void
    {
        if ($this->connection->isTransactionActive()) {
            $this->connection->rollBack();
        }
        $this->em->clear();

        parent::tearDown();
    }

    public function testUpsertInsertsANewInvoice(): void
    {
        $partner = $this->createPartner();
        $idExternal = $this->uniqueId();

        $this->repository->upsertBatch([
            new InvoiceInput($idExternal, 'Frank Green', 670.43, Currency::EUR, $partner->name),
        ]);

        $row = $this->fetchInvoice($idExternal, $partner->id);

        self::assertNotNull($row);
        self::assertSame('Frank Green', $row['name']);
        self::assertSame(670.43, (float) $row['amount']);
        self::assertSame('EUR', $row['currency']);
    }

    public function testUpsertUpdatesInPlaceOnSameBusinessKey(): void
    {
        $partner = $this->createPartner();
        $idExternal = $this->uniqueId();

        // Première écriture.
        $this->repository->upsertBatch([
            new InvoiceInput($idExternal, 'Frank Green', 670.43, Currency::EUR, $partner->name),
        ]);

        // Même couple (id_external, partenaire) => mise à jour, pas de doublon.
        $this->repository->upsertBatch([
            new InvoiceInput($idExternal, 'Frank Green (corrigé)', 999.99, Currency::USD, $partner->name),
        ]);

        $count = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM invoice WHERE id_external = :id AND partner_id = :pid',
            ['id' => $idExternal, 'pid' => $partner->id],
        );
        self::assertSame(1, (int) $count);

        $row = $this->fetchInvoice($idExternal, $partner->id);
        self::assertNotNull($row);
        self::assertSame('Frank Green (corrigé)', $row['name']);
        self::assertSame(999.99, (float) $row['amount']);
        self::assertSame('USD', $row['currency']);
    }

    public function testSameIdExternalAcrossPartnersCoexist(): void
    {
        $partnerA = $this->createPartner();
        $partnerB = $this->createPartner();
        $idExternal = $this->uniqueId();

        $this->repository->upsertBatch([
            new InvoiceInput($idExternal, 'A', 10.0, Currency::EUR, $partnerA->name),
            new InvoiceInput($idExternal, 'B', 20.0, Currency::EUR, $partnerB->name),
        ]);

        self::assertNotNull($this->fetchInvoice($idExternal, $partnerA->id));
        self::assertNotNull($this->fetchInvoice($idExternal, $partnerB->id));
    }

    public function testUnknownPartnerThrows(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('inconnu');

        $this->repository->upsertBatch([
            new InvoiceInput($this->uniqueId(), 'Frank Green', 10.0, Currency::EUR, 'Partenaire qui n\'existe pas '.uniqid()),
        ]);
    }

    private function createPartner(): Partner
    {
        $partner = new Partner();
        $partner->name = 'IT Partner '.uniqid('', true);

        $this->em->persist($partner);
        $this->em->flush();

        return $partner;
    }

    private function uniqueId(): string
    {
        return 'IT-'.uniqid('', true);
    }

    /**
     * @return array{name: string, amount: float|string, currency: string}|null
     */
    private function fetchInvoice(string $idExternal, int $partnerId): ?array
    {
        $row = $this->connection->fetchAssociative(
            'SELECT name, amount, currency FROM invoice WHERE id_external = :id AND partner_id = :pid',
            ['id' => $idExternal, 'pid' => $partnerId],
        );

        return false === $row ? null : $row;
    }
}
