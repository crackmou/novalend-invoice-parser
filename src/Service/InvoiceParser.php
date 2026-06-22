<?php

declare(strict_types=1);


namespace App\Service;

use App\Enum\Currency;
use App\Repository\InvoiceRepository;


class InvoiceParser
{
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

        foreach ($invoices as $invoice) {
            $this->invoiceRepository->upsert(
                (string) $invoice['id_externe'],
                (string) $invoice['nom'],
                (float) $invoice['montant'],
                Currency::from((string) $invoice['devise']),
                (string) $invoice['partenaire'],
            );
        }
    }

    private function parseCsv(string $filePath): void
    {
        $rows = array_map(
            static fn (string $row) => str_getcsv($row, "\t", '"', ''),
            file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)
        );

        // Colonnes du CSV : id_externe, montant, devise, nom, partenaire, date
        foreach ($rows as $row) {
            $this->invoiceRepository->upsert(
                (string) $row[0],
                (string) $row[3],
                (float) $row[1],
                Currency::from((string) $row[2]),
                (string) $row[4],
            );
        }
    }
}
