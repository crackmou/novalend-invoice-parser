<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration de données : crée les partenaires de référence
 * (La banque postale, Olinn, Solufinance).
 *
 * Le rattachement des factures à leur partenaire est désormais réalisé au
 * moment du parsing (cf. InvoiceRepository::upsert), pas dans cette migration.
 *
 * Idempotente : les partenaires ne sont insérés que s'ils n'existent pas déjà.
 */
final class Version20260622150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed reference partners';
    }

    public function up(Schema $schema): void
    {
        // Insertion des partenaires (uniquement s'ils n'existent pas déjà).
        $this->addSql(<<<'SQL'
            INSERT INTO partner (id, name)
            SELECT nextval('partner_id_seq'), v.name
            FROM (VALUES ('La banque postale'), ('Olinn'), ('Solufinance')) AS v(name)
            WHERE NOT EXISTS (SELECT 1 FROM partner p WHERE p.name = v.name)
            SQL);
    }

    public function down(Schema $schema): void
    {
        $partners = "'La banque postale', 'Olinn', 'Solufinance'";

        // Détache les factures de ces partenaires, puis supprime les partenaires.
        $this->addSql("UPDATE invoice SET partner_id = NULL WHERE partner_id IN (SELECT id FROM partner WHERE name IN ($partners))");
        $this->addSql("DELETE FROM partner WHERE name IN ($partners)");
    }
}
