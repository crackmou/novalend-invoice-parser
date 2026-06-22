<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'App\Repository\InvoiceRepository')]
// La clé métier unique d'une facture est le couple (id externe, partenaire) ;
// un même nom peut porter plusieurs factures.
#[ORM\UniqueConstraint(name: 'uniq_invoice_id_external', columns: ['id_external', 'partner_id'])]
class Invoice
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: 'integer')]
    public int $id;

    #[ORM\Column(type: 'string')]
    public string $name;

    #[ORM\Column(type: 'float')]
    public float $amount;

    #[ORM\Column(type: 'string')]
    public string $currency;

    #[ORM\Column(name: 'id_external', type: 'string', nullable: true)]
    public ?string $idExternal = null;

    #[ORM\ManyToOne(targetEntity: Partner::class)]
    #[ORM\JoinColumn(name: 'partner_id', referencedColumnName: 'id', nullable: true)]
    public ?Partner $partner = null;
}
