<?php

declare(strict_types=1);

namespace App\Reader;

use App\Exception\UnsupportedFileFormatException;

/**
 * Sélectionne le reader capable de traiter un fichier donné, d'après son
 * extension. Les readers sont injectés via le tag "app.invoice_reader".
 */
final class InvoiceReaderRegistry
{
    /**
     * @param iterable<InvoiceReaderInterface> $readers
     */
    public function __construct(private readonly iterable $readers)
    {
    }

    public function readerFor(string $filePath): InvoiceReaderInterface
    {
        $extension = strtolower(pathinfo($filePath, \PATHINFO_EXTENSION));

        foreach ($this->readers as $reader) {
            if ($reader->supports($extension)) {
                return $reader;
            }
        }

        throw new UnsupportedFileFormatException(sprintf('Extension de fichier non supportée : "%s".', $extension));
    }
}
