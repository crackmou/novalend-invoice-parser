<?php

declare(strict_types=1);

namespace App\Reader;

use App\Dto\InvoiceInput;
use App\Exception\InvalidInvoiceDataException;

final class CsvInvoiceReader implements InvoiceReaderInterface
{
    private const DELIMITER = "\t";

    /** Colonnes attendues : id_externe, montant, devise, nom, partenaire, date. */
    private const COL_ID_EXTERNAL = 0;
    private const COL_AMOUNT = 1;
    private const COL_CURRENCY = 2;
    private const COL_NAME = 3;
    private const COL_PARTNER = 4;
    private const MIN_COLUMNS = 5;

    public function supports(string $extension): bool
    {
        return 'csv' === $extension;
    }

    public function read(string $filePath): iterable
    {
        $handle = fopen($filePath, 'r');
        if (false === $handle) {
            throw new \RuntimeException(sprintf('Impossible d\'ouvrir le fichier : "%s".', $filePath));
        }

        try {
            $line = 0;
            while (($row = fgetcsv($handle, 0, self::DELIMITER, '"', '')) !== false) {
                ++$line;

                // Ligne vide éventuelle.
                if ([null] === $row) {
                    continue;
                }

                if (\count($row) < self::MIN_COLUMNS) {
                    throw new InvalidInvoiceDataException(sprintf('Ligne %d : %d colonnes attendues, %d trouvée(s).', $line, self::MIN_COLUMNS, \count($row)));
                }

                yield InvoiceInput::fromRaw(
                    (string) $row[self::COL_ID_EXTERNAL],
                    (string) $row[self::COL_NAME],
                    (string) $row[self::COL_AMOUNT],
                    (string) $row[self::COL_CURRENCY],
                    (string) $row[self::COL_PARTNER],
                );
            }
        } finally {
            fclose($handle);
        }
    }
}
