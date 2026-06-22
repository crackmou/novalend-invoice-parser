<?php

declare(strict_types=1);


namespace App\Repository;

use App\Entity\Invoice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class InvoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Invoice::class);
    }

    /**
     * Insère une facture, ou met à jour le montant / la devise si une facture
     * du même nom existe déjà (« insert on duplicate key »).
     */
    public function upsert(string $name, float $amount, string $currency): void
    {
        $this->getEntityManager()->getConnection()->executeStatement(
            <<<'SQL'
                INSERT INTO invoice (id, name, amount, currency)
                VALUES (nextval('invoice_id_seq'), :name, :amount, :currency)
                ON CONFLICT (name) DO UPDATE
                SET amount = EXCLUDED.amount,
                    currency = EXCLUDED.currency
                SQL,
            [
                'name' => $name,
                'amount' => $amount,
                'currency' => $currency,
            ]
        );
    }
}
