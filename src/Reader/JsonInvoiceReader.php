<?php

declare(strict_types=1);

namespace App\Reader;

use App\Dto\InvoiceInput;
use App\Exception\InvalidInvoiceDataException;

final class JsonInvoiceReader implements InvoiceReaderInterface
{
    public function supports(string $extension): bool
    {
        return 'json' === $extension;
    }

    public function read(string $filePath): iterable
    {
        $content = file_get_contents($filePath);
        if (false === $content) {
            throw new \RuntimeException(sprintf('Impossible de lire le fichier : "%s".', $filePath));
        }

        try {
            $rows = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new InvalidInvoiceDataException(sprintf('JSON invalide dans "%s" : %s', $filePath, $e->getMessage()), 0, $e);
        }

        if (!is_array($rows)) {
            throw new InvalidInvoiceDataException(sprintf('Le fichier JSON ne contient pas une liste de factures : "%s".', $filePath));
        }

        foreach ($rows as $row) {
            yield InvoiceInput::fromRaw(
                $this->field($row, 'id_externe'),
                $this->field($row, 'nom'),
                $this->field($row, 'montant'),
                $this->field($row, 'devise'),
                $this->field($row, 'partenaire'),
            );
        }
    }

    private function field(mixed $row, string $key): string
    {
        if (!is_array($row) || !array_key_exists($key, $row)) {
            throw new InvalidInvoiceDataException(sprintf('Champ obligatoire "%s" absent.', $key));
        }

        $value = $row[$key];
        if (!is_scalar($value)) {
            throw new InvalidInvoiceDataException(sprintf('Champ "%s" : valeur scalaire attendue.', $key));
        }

        return (string) $value;
    }
}
