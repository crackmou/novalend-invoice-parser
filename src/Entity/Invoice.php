<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'App\Repository\InvoiceRepository')]
#[ORM\UniqueConstraint(name: 'uniq_invoice_name', columns: ['name'])]
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
}
