<?php

declare(strict_types=1);

namespace App\Dto;

use App\Enum\Currency;
use App\Exception\InvalidInvoiceDataException;

/**
 * Représentation immuable et typée d'une facture issue d'un fichier source,
 * indépendante du format d'origine (CSV, JSON, ...) et de la persistance.
 *
 * C'est le seul endroit qui connaît les règles de validation d'une facture :
 * les readers se contentent d'extraire des valeurs brutes et délèguent ici.
 */
final class InvoiceInput
{
    public function __construct(
        public readonly string $idExternal,
        public readonly string $name,
        public readonly float $amount,
        public readonly Currency $currency,
        public readonly string $partnerName,
    ) {
        if ('' === trim($idExternal)) {
            throw new InvalidInvoiceDataException('Identifiant externe (id_externe) manquant.');
        }
        if ('' === trim($name)) {
            throw new InvalidInvoiceDataException(sprintf('Nom manquant pour la facture "%s".', $idExternal));
        }
        if ('' === trim($partnerName)) {
            throw new InvalidInvoiceDataException(sprintf('Partenaire manquant pour la facture "%s".', $idExternal));
        }
        if ($amount < 0) {
            throw new InvalidInvoiceDataException(sprintf('Montant négatif (%s) pour la facture "%s".', $amount, $idExternal));
        }
    }

    /**
     * Construit une facture à partir de valeurs brutes (chaînes telles que
     * lues dans le fichier), en validant le typage métier.
     */
    public static function fromRaw(
        string $idExternal,
        string $name,
        string $amount,
        string $currencyCode,
        string $partnerName,
    ): self {
        if (!is_numeric($amount)) {
            throw new InvalidInvoiceDataException(sprintf('Montant invalide "%s" pour la facture "%s".', $amount, $idExternal));
        }

        $currency = Currency::tryFrom($currencyCode);
        if (null === $currency) {
            throw new InvalidInvoiceDataException(sprintf('Devise non supportée "%s" pour la facture "%s".', $currencyCode, $idExternal));
        }

        return new self($idExternal, $name, (float) $amount, $currency, $partnerName);
    }
}
