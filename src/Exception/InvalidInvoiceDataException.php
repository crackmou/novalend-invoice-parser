<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * Levée lorsqu'une ligne/entrée ne peut pas être transformée en facture valide
 * (devise inconnue, montant non numérique, champ obligatoire absent, ...).
 */
final class InvalidInvoiceDataException extends \RuntimeException
{
}
