<?php

declare(strict_types=1);

namespace App\Reader;

use App\Dto\InvoiceInput;

/**
 * Contrat d'un lecteur de fichier de factures. Une implémentation par format.
 *
 * Ajouter un format (XML, XLSX, ...) = ajouter une classe qui implémente cette
 * interface ; aucun code existant n'est modifié (Open/Closed).
 */
interface InvoiceReaderInterface
{
    /**
     * @param string $extension extension du fichier, en minuscules, sans le point
     */
    public function supports(string $extension): bool;

    /**
     * Lit le fichier et émet les factures au fil de l'eau.
     *
     * Le retour est volontairement un générateur : on ne charge jamais
     * l'intégralité du fichier en mémoire.
     *
     * @return iterable<InvoiceInput>
     */
    public function read(string $filePath): iterable;
}
