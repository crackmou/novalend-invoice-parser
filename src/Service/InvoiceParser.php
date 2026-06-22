<?php

declare(strict_types=1);


namespace App\Service;

use App\Enum\Currency;
use App\Repository\InvoiceRepository;


class InvoiceParser
{
    /**
     * Nombre de factures accumulées avant d'envoyer un lot au repository, afin de
     * ne jamais garder l'intégralité du fichier en mémoire.
     */
    private const FLUSH_SIZE = 1000;

    private InvoiceRepository $invoiceRepository;

    public function __construct(InvoiceRepository $invoiceRepository)
    {
        $this->invoiceRepository = $invoiceRepository;
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

        $batch = [];
        foreach ($invoices as $invoice) {
            $batch[] = [
                'idExternal' => (string) $invoice['id_externe'],
                'name' => (string) $invoice['nom'],
                'amount' => (float) $invoice['montant'],
                'currency' => Currency::from((string) $invoice['devise']),
                'partnerName' => (string) $invoice['partenaire'],
            ];

            if (count($batch) >= self::FLUSH_SIZE) {
                $this->invoiceRepository->upsertBatch($batch);
                $batch = [];
            }
        }

        if ($batch !== []) {
            $this->invoiceRepository->upsertBatch($batch);
        }
    }

    private function parseCsv(string $filePath): void
    {
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new \RuntimeException(sprintf('Impossible d\'ouvrir le fichier : "%s".', $filePath));
        }

        try {
            $batch = [];

            // Colonnes du CSV : id_externe, montant, devise, nom, partenaire, date
            while (($row = fgetcsv($handle, 0, "\t", '"', '')) !== false) {
                // Ignore les lignes vides éventuelles.
                if ($row === [null] || $row === []) {
                    continue;
                }

                $batch[] = [
                    'idExternal' => (string) $row[0],
                    'name' => (string) $row[3],
                    'amount' => (float) $row[1],
                    'currency' => Currency::from((string) $row[2]),
                    'partnerName' => (string) $row[4],
                ];

                if (count($batch) >= self::FLUSH_SIZE) {
                    $this->invoiceRepository->upsertBatch($batch);
                    $batch = [];
                }
            }

            if ($batch !== []) {
                $this->invoiceRepository->upsertBatch($batch);
            }
        } finally {
            fclose($handle);
        }
    }
}
