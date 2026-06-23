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
    private const CSV_EXTENSION = 'csv';
    private const JSON_EXTENSION = 'json';

    private InvoiceRepository $invoiceRepository;

    public function __construct(InvoiceRepository $invoiceRepository)
    {
        $this->invoiceRepository = $invoiceRepository;
    }

    public function parse(string $filePath): void
    {
        if (!file_exists($filePath)) {
            throw new \Exception(sprintf('Fichier introuvable : "%s".', $filePath));
        }
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        match ($ext) {
            self::CSV_EXTENSION => $this->parseCSV($filePath),
            self::JSON_EXTENSION => $this->parseJson($filePath),
            default => throw new \Exception(sprintf('Extension de fichier non supportée : "%s".', $ext)),
        };
    }

    private function parseJson(string $filePath): void
    {
        $content = file_get_contents($filePath);
        $invoices = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        $batch = [];
        foreach ($invoices as $invoice) {
            $batch[] = $this->getElement(
                (string) $invoice['id_externe'],
                (string) $invoice['nom'],
                (float) $invoice['montant'],
                Currency::from((string) $invoice['devise']),
                (string) $invoice['partenaire']
            );

            if (count($batch) >= self::FLUSH_SIZE) {
                $this->invoiceRepository->upsertBatch($batch);
                $batch = [];
            }
        }

        if ([] !== $batch) {
            $this->invoiceRepository->upsertBatch($batch);
        }
    }

    private function parseCsv(string $filePath): void
    {
        $handle = fopen($filePath, 'r');
        if (false === $handle) {
            throw new \RuntimeException(sprintf('Impossible d\'ouvrir le fichier : "%s".', $filePath));
        }

        try {
            $batch = [];

            // Colonnes du CSV : id_externe, montant, devise, nom, partenaire, date
            while (($row = fgetcsv($handle, 0, "\t", '"', '')) !== false) {
                // Ignore les lignes vides éventuelles.
                if ($row === [null] || [] === $row) {
                    continue;
                }

                $batch[] = $this->getElement(
                    (string) $row[0],
                    (string) $row[3],
                    (float) $row[1],
                    Currency::from((string) $row[2]),
                    (string) $row[4]
                );
                if (count($batch) >= self::FLUSH_SIZE) {
                    $this->invoiceRepository->upsertBatch($batch);
                    $batch = [];
                }
            }

            if ([] !== $batch) {
                $this->invoiceRepository->upsertBatch($batch);
            }
        } finally {
            fclose($handle);
        }
    }

    private function getElement(
        string $idExternal,
        string $name,
        float $amount,
        Currency $currency,
        string $partnerName,
    ): array {
        return [
            'idExternal' => $idExternal,
            'name' => $name,
            'amount' => $amount,
            'currency' => $currency,
            'partnerName' => $partnerName,
        ];
    }
}
