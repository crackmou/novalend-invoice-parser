<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajoute une contrainte d'unicité sur invoice.name afin de permettre
 * l'upsert (INSERT ... ON CONFLICT (name) DO UPDATE) du parser de factures.
 */
final class Version20260622120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add unique constraint on invoice.name';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT uniq_invoice_name UNIQUE (name)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE invoice DROP CONSTRAINT uniq_invoice_name');
    }
}
