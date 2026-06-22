<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * La contrainte d'unicité de la facture porte désormais sur le couple
 * (id_external, partner_id) plutôt que sur id_external seul.
 */
final class Version20260622170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make uniq_invoice_id_external composite on (id_external, partner_id)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE invoice DROP CONSTRAINT uniq_invoice_id_external');
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT uniq_invoice_id_external UNIQUE (id_external, partner_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE invoice DROP CONSTRAINT uniq_invoice_id_external');
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT uniq_invoice_id_external UNIQUE (id_external)');
    }
}
