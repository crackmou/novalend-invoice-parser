<?php

declare(strict_types=1);


namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;


class InvoiceParser
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function parse(string $filePath): void
    {
        if (str_contains($filePath, 'json')) {
            $this->parseJson($filePath);
        } elseif (str_contains($filePath, 'csv')) {
            $this->parseCsv($filePath);
        }
    }

    private function parseJson(string $filePath): void
    {
        $content = file_get_contents($filePath);
        $invoices = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        foreach ($invoices as $invoice) {
            $this->upsert(
                (string) $invoice['nom'],
                (float) $invoice['montant'],
                (string) $invoice['devise'],
            );
        }
    }

    private function parseCsv(string $filePath): void
    {
        $rows = array_map(
            static fn (string $row) => str_getcsv($row, "\t"),
            file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)
        );

        // Colonnes du CSV : montant, devise, nom, date
        foreach ($rows as $row) {
            $this->upsert(
                (string) $row[2],
                (float) $row[0],
                (string) $row[1],
            );
        }
    }

    /**
     * Insère une facture, ou met à jour le montant / la devise si une facture
     * du même nom existe déjà (« insert on duplicate key »).
     */
    private function upsert(string $name, float $amount, string $currency): void
    {
        $this->em->getConnection()->executeStatement(
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
