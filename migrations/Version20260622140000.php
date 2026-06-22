<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Crée la table partner (id, name) et ajoute à invoice les colonnes
 * id_external et partner_id (clé étrangère vers partner). Les deux colonnes
 * sont nullables et restent à NULL par défaut : elles seront renseignées
 * a posteriori.
 */
final class Version20260622140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add partner table and invoice.id_external / invoice.partner_id (nullable)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE partner_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE partner (id INT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))');

        $this->addSql('ALTER TABLE invoice ADD id_external VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE invoice ADD partner_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT fk_invoice_partner FOREIGN KEY (partner_id) REFERENCES partner (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_invoice_partner ON invoice (partner_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE invoice DROP CONSTRAINT fk_invoice_partner');
        $this->addSql('DROP INDEX idx_invoice_partner');
        $this->addSql('ALTER TABLE invoice DROP partner_id');
        $this->addSql('ALTER TABLE invoice DROP id_external');

        $this->addSql('DROP TABLE partner');
        $this->addSql('DROP SEQUENCE partner_id_seq');
    }
}
