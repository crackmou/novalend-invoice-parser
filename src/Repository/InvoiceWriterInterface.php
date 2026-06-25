<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\InvoiceInput;

/**
 * Abstraction d'écriture des factures, indépendante de la stratégie de
 * persistance (SQL natif, ORM, fake en mémoire pour les tests, ...).
 *
 * Le parser (haut niveau) dépend de ce contrat, pas d'une implémentation
 * concrète : c'est l'inversion de dépendance (le « D » de SOLID).
 */
interface InvoiceWriterInterface
{
    /**
     * Insère ou met à jour un lot de factures sur leur clé métier
     * (id externe, partenaire).
     *
     * @param array<int, InvoiceInput> $invoices
     */
    public function upsertBatch(array $invoices): void;
}
