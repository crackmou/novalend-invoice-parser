<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * La clé métier unique d'une facture devient son id externe (id_external).
 * Le nom n'est plus unique : une même personne peut porter plusieurs factures.
 */
final class Version20260622160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make invoice.id_external the unique business key (name no longer unique)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT uniq_invoice_id_external UNIQUE (id_external)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE invoice DROP CONSTRAINT uniq_invoice_id_external');
    }
}
