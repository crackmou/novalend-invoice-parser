<?php

declare(strict_types=1);


namespace App\Repository;

use App\Entity\Invoice;
use App\Entity\Partner;
use App\Enum\Currency;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class InvoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Invoice::class);
    }

    /**
     * Insère une facture, ou la met à jour si une facture existe déjà pour le
     * même couple (id externe, partenaire) — clé métier « insert on duplicate key ».
     *
     * @throws \RuntimeException si le partenaire référencé n'existe pas.
     */
    public function upsert(
        string $idExternal,
        string $name,
        float $amount,
        Currency $currency,
        string $partnerName
    ): void {
        $partner = $this->getEntityManager()
            ->getRepository(Partner::class)
            ->findOneBy(['name' => $partnerName]);

        if ($partner === null) {
            throw new \RuntimeException(sprintf('Partenaire inconnu : "%s".', $partnerName));
        }

        $this->getEntityManager()->getConnection()->executeStatement(
            <<<'SQL'
                INSERT INTO invoice (id, id_external, name, amount, currency, partner_id)
                VALUES (nextval('invoice_id_seq'), :id_external, :name, :amount, :currency, :partner_id)
                ON CONFLICT (id_external, partner_id) DO UPDATE
                SET name = EXCLUDED.name,
                    amount = EXCLUDED.amount,
                    currency = EXCLUDED.currency
                SQL,
            [
                'id_external' => $idExternal,
                'name' => $name,
                'amount' => $amount,
                'currency' => $currency->value,
                'partner_id' => $partner->id,
            ]
        );
    }
}
