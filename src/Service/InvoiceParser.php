<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Invoice;
use App\Reader\InvoiceReaderRegistry;
use App\Repository\InvoiceRepository;
use App\Repository\PartnerRepository;
use Doctrine\ORM\EntityManagerInterface;

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
        private readonly EntityManagerInterface $entityManager,
        private readonly PartnerRepository $partnerRepository,
        private readonly InvoiceRepository $invoiceRepository,
    ) {
    }

    public function parse(string $filePath): void
    {
        if (!is_file($filePath)) {
            throw new \RuntimeException(sprintf('Fichier introuvable : "%s".', $filePath));
        }

        $reader = $this->readers->readerFor($filePath);

        $partners = [];
        foreach ($reader->read($filePath) as $invoiceFile) {
            if (!isset($partners[$invoiceFile->partnerName])) {
                $partner = $this->partnerRepository->findOneByName($invoiceFile->partnerName);
                if (null === $partner) {
                    throw new \RuntimeException(sprintf('Partenaire introuvable : "%s".', $invoiceFile->partnerName));
                }
                $partners[$invoiceFile->partnerName] = $partner;
            }
            $partner = $partners[$invoiceFile->partnerName];
            $invoice = $this->invoiceRepository->findOneBy([
                'idExternal' => $invoiceFile->idExternal,
                'partner' => $partner,
            ]);
            if (null === $invoice) {
                $invoice = new Invoice();
            }
            $invoice->name = $invoiceFile->name;
            $invoice->amount = $invoiceFile->amount;
            $invoice->currency = $invoiceFile->currency;
            $invoice->idExternal = $invoiceFile->idExternal;
            $invoice->partner = $partner;
            $this->entityManager->persist($invoice);
        }
        $this->entityManager->flush();
    }
}
