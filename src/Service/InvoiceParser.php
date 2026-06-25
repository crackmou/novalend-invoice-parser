<?php

declare(strict_types=1);

namespace App\Service;

use App\Reader\InvoiceReaderRegistry;
use App\Repository\InvoiceWriterInterface;

/**
 * Orchestre l'import : choisit le reader adapté au fichier, agrège les factures
 * par lots et délègue la persistance au repository.
 *
 * Cette classe ne connaît plus aucun format de fichier ni aucune règle de
 * mapping : tout cela vit dans les readers et le DTO.
 */
final class InvoiceParser
{
    /**
     * Nombre de factures accumulées avant d'envoyer un lot au repository, afin
     * de ne jamais garder l'intégralité du fichier en mémoire.
     */
    private const FLUSH_SIZE = 1000;

    public function __construct(
        private readonly InvoiceReaderRegistry $readers,
        private readonly InvoiceWriterInterface $invoiceWriter,
    ) {
    }

    public function parse(string $filePath): void
    {
        if (!is_file($filePath)) {
            throw new \RuntimeException(sprintf('Fichier introuvable : "%s".', $filePath));
        }

        $reader = $this->readers->readerFor($filePath);

        $batch = [];
        foreach ($reader->read($filePath) as $invoice) {
            $batch[] = $invoice;

            if (\count($batch) >= self::FLUSH_SIZE) {
                $this->invoiceWriter->upsertBatch($batch);
                $batch = [];
            }
        }

        if ([] !== $batch) {
            $this->invoiceWriter->upsertBatch($batch);
        }
    }
}
