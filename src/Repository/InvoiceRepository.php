<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\InvoiceInput;
use App\Entity\Invoice;
use App\Entity\Partner;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Invoice>
 */
class InvoiceRepository extends ServiceEntityRepository implements InvoiceWriterInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Invoice::class);
    }

    /**
     * Nombre maximum de factures insérées par requête SQL.
     *
     * PostgreSQL limite à 65535 le nombre de paramètres liés par requête ; avec
     * 5 paramètres par ligne on reste très en deçà avec ce palier.
     */
    private const BATCH_SIZE = 1000;

    /**
     * Insère un lot de factures en une seule requête (insert on duplicate key),
     * ou les met à jour si elles existent déjà pour le même couple
     * (id externe, partenaire).
     *
     * @param array<int, InvoiceInput> $invoices
     *
     * @throws \RuntimeException si un partenaire référencé n'existe pas
     */
    public function upsertBatch(array $invoices): void
    {
        if ([] === $invoices) {
            return;
        }

        $partnerIds = $this->resolvePartnerIds($invoices);
        $connection = $this->getEntityManager()->getConnection();

        foreach (array_chunk($invoices, self::BATCH_SIZE) as $chunk) {
            $valuePlaceholders = [];
            $parameters = [];

            foreach ($chunk as $i => $invoice) {
                $valuePlaceholders[] = sprintf(
                    "(nextval('invoice_id_seq'), :id_external_%1\$d, :name_%1\$d, :amount_%1\$d, :currency_%1\$d, :partner_id_%1\$d)",
                    $i
                );

                $parameters["id_external_$i"] = $invoice->idExternal;
                $parameters["name_$i"] = $invoice->name;
                $parameters["amount_$i"] = $invoice->amount;
                $parameters["currency_$i"] = $invoice->currency->value;
                $parameters["partner_id_$i"] = $partnerIds[$invoice->partnerName];
            }

            $sql = sprintf(
                <<<'SQL'
                    INSERT INTO invoice (id, id_external, name, amount, currency, partner_id)
                    VALUES %s
                    ON CONFLICT (id_external, partner_id) DO UPDATE
                    SET name = EXCLUDED.name,
                        amount = EXCLUDED.amount,
                        currency = EXCLUDED.currency
                    SQL,
                implode(', ', $valuePlaceholders)
            );

            $connection->executeStatement($sql, $parameters);
        }
    }

    /**
     * Résout en une seule requête l'identifiant de chaque partenaire référencé
     * par le lot, indexé par nom.
     *
     * @param array<int, InvoiceInput> $invoices
     *
     * @return array<string, int> nom du partenaire => identifiant
     *
     * @throws \RuntimeException si un partenaire référencé n'existe pas
     */
    private function resolvePartnerIds(array $invoices): array
    {
        $names = array_values(array_unique(array_map(
            static fn (InvoiceInput $invoice): string => $invoice->partnerName,
            $invoices,
        )));

        $partners = $this->getEntityManager()
            ->getRepository(Partner::class)
            ->findBy(['name' => $names]);

        $partnerIds = [];
        foreach ($partners as $partner) {
            $partnerIds[$partner->name] = $partner->id;
        }

        $unknown = array_diff($names, array_keys($partnerIds));
        if ([] !== $unknown) {
            throw new \RuntimeException(sprintf('Partenaire(s) inconnu(s) : "%s".', implode('", "', $unknown)));
        }

        return $partnerIds;
    }
}
