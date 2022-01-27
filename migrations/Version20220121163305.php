<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220121163305 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_CF2713FD55B127A4');
        $this->addSql('CREATE TEMPORARY TABLE __temp__host AS SELECT id, added_by_id, created_at, display_name, fully_qualified_name, ipv4_address, ipv6_address, last_contacted_at, updated_at FROM host');
        $this->addSql('DROP TABLE host');
        $this->addSql('CREATE TABLE host (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, added_by_id INTEGER NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , display_name VARCHAR(255) NOT NULL COLLATE BINARY, fully_qualified_name VARCHAR(255) NOT NULL COLLATE BINARY, ipv4_address BLOB DEFAULT NULL, ipv6_address BLOB DEFAULT NULL, last_contacted_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_CF2713FD55B127A4 FOREIGN KEY (added_by_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO host (id, added_by_id, created_at, display_name, fully_qualified_name, ipv4_address, ipv6_address, last_contacted_at, updated_at) SELECT id, added_by_id, created_at, display_name, fully_qualified_name, ipv4_address, ipv6_address, last_contacted_at, updated_at FROM __temp__host');
        $this->addSql('DROP TABLE __temp__host');
        $this->addSql('CREATE INDEX IDX_CF2713FD55B127A4 ON host (added_by_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_CF2713FD55B127A4');
        $this->addSql('CREATE TEMPORARY TABLE __temp__host AS SELECT id, added_by_id, display_name, fully_qualified_name, ipv4_address, ipv6_address, last_contacted_at, created_at, updated_at FROM host');
        $this->addSql('DROP TABLE host');
        $this->addSql('CREATE TABLE host (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, added_by_id INTEGER NOT NULL, display_name VARCHAR(255) NOT NULL, fully_qualified_name VARCHAR(255) NOT NULL, ipv4_address BLOB DEFAULT NULL, ipv6_address BLOB DEFAULT NULL, last_contacted_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL)');
        $this->addSql('INSERT INTO host (id, added_by_id, display_name, fully_qualified_name, ipv4_address, ipv6_address, last_contacted_at, created_at, updated_at) SELECT id, added_by_id, display_name, fully_qualified_name, ipv4_address, ipv6_address, last_contacted_at, created_at, updated_at FROM __temp__host');
        $this->addSql('DROP TABLE __temp__host');
        $this->addSql('CREATE INDEX IDX_CF2713FD55B127A4 ON host (added_by_id)');
    }
}
