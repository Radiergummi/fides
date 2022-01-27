<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220123104011 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE certificate_authority (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, comment CLOB NOT NULL, public_key CLOB NOT NULL, revoked_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        )');
        $this->addSql('CREATE TABLE host_certificate (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, certificate_authority_id INTEGER NOT NULL, content CLOB NOT NULL, public_key CLOB NOT NULL, revoked_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , serial_number BIGINT NOT NULL, valid_from DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , valid_until DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        )');
        $this->addSql('CREATE INDEX IDX_58E8F1B4997CC58F ON host_certificate (certificate_authority_id)');
        $this->addSql('CREATE TABLE user_certificate (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, certificate_authority_id INTEGER NOT NULL, user_id INTEGER NOT NULL, agent_forwarding_enabled BOOLEAN NOT NULL, allowed_source_addresses CLOB NOT NULL --(DC2Type:json)
        , content CLOB NOT NULL, force_command CLOB DEFAULT NULL, port_forwarding_enabled BOOLEAN NOT NULL, pty_allocation_enabled BOOLEAN NOT NULL, public_key CLOB NOT NULL, revoked_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , serial_number BIGINT NOT NULL, user_rc_execution_enabled BOOLEAN NOT NULL, valid_from DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , valid_until DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , x11_forwarding_enabled BOOLEAN NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        )');
        $this->addSql('CREATE INDEX IDX_888713DF997CC58F ON user_certificate (certificate_authority_id)');
        $this->addSql('CREATE INDEX IDX_888713DFA76ED395 ON user_certificate (user_id)');
        $this->addSql('CREATE TABLE user_certificate_security_zone (user_certificate_id INTEGER NOT NULL, security_zone_id INTEGER NOT NULL, PRIMARY KEY(user_certificate_id, security_zone_id))');
        $this->addSql('CREATE INDEX IDX_F2F604C0F2A7E0A4 ON user_certificate_security_zone (user_certificate_id)');
        $this->addSql('CREATE INDEX IDX_F2F604C0F2C734B6 ON user_certificate_security_zone (security_zone_id)');
        $this->addSql('DROP INDEX IDX_CF2713FD55B127A4');
        $this->addSql('CREATE TEMPORARY TABLE __temp__host AS SELECT id, added_by_id, created_at, display_name, fully_qualified_name, ipv4_address, ipv6_address, last_contacted_at, updated_at FROM host');
        $this->addSql('DROP TABLE host');
        $this->addSql('CREATE TABLE host (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, added_by_id INTEGER NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , display_name VARCHAR(255) NOT NULL COLLATE BINARY, fully_qualified_name VARCHAR(255) NOT NULL COLLATE BINARY, ipv4_address BLOB DEFAULT NULL --(DC2Type:ip)
        , ipv6_address BLOB DEFAULT NULL --(DC2Type:ip)
        , last_contacted_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_CF2713FD55B127A4 FOREIGN KEY (added_by_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO host (id, added_by_id, created_at, display_name, fully_qualified_name, ipv4_address, ipv6_address, last_contacted_at, updated_at) SELECT id, added_by_id, created_at, display_name, fully_qualified_name, ipv4_address, ipv6_address, last_contacted_at, updated_at FROM __temp__host');
        $this->addSql('DROP TABLE __temp__host');
        $this->addSql('CREATE INDEX IDX_CF2713FD55B127A4 ON host (added_by_id)');
        $this->addSql('DROP INDEX IDX_597900C11FB8D185');
        $this->addSql('DROP INDEX IDX_597900C1F2C734B6');
        $this->addSql('CREATE TEMPORARY TABLE __temp__host_security_zone AS SELECT host_id, security_zone_id FROM host_security_zone');
        $this->addSql('DROP TABLE host_security_zone');
        $this->addSql('CREATE TABLE host_security_zone (host_id INTEGER NOT NULL, security_zone_id INTEGER NOT NULL, PRIMARY KEY(host_id, security_zone_id), CONSTRAINT FK_597900C11FB8D185 FOREIGN KEY (host_id) REFERENCES host (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_597900C1F2C734B6 FOREIGN KEY (security_zone_id) REFERENCES security_zone (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO host_security_zone (host_id, security_zone_id) SELECT host_id, security_zone_id FROM __temp__host_security_zone');
        $this->addSql('DROP TABLE __temp__host_security_zone');
        $this->addSql('CREATE INDEX IDX_597900C11FB8D185 ON host_security_zone (host_id)');
        $this->addSql('CREATE INDEX IDX_597900C1F2C734B6 ON host_security_zone (security_zone_id)');
        $this->addSql('DROP INDEX IDX_9174E196F2C734B6');
        $this->addSql('DROP INDEX IDX_9174E196A76ED395');
        $this->addSql('CREATE TEMPORARY TABLE __temp__user_security_zone AS SELECT user_id, security_zone_id FROM user_security_zone');
        $this->addSql('DROP TABLE user_security_zone');
        $this->addSql('CREATE TABLE user_security_zone (user_id INTEGER NOT NULL, security_zone_id INTEGER NOT NULL, PRIMARY KEY(user_id, security_zone_id), CONSTRAINT FK_9174E196A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_9174E196F2C734B6 FOREIGN KEY (security_zone_id) REFERENCES security_zone (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO user_security_zone (user_id, security_zone_id) SELECT user_id, security_zone_id FROM __temp__user_security_zone');
        $this->addSql('DROP TABLE __temp__user_security_zone');
        $this->addSql('CREATE INDEX IDX_9174E196F2C734B6 ON user_security_zone (security_zone_id)');
        $this->addSql('CREATE INDEX IDX_9174E196A76ED395 ON user_security_zone (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE certificate_authority');
        $this->addSql('DROP TABLE host_certificate');
        $this->addSql('DROP TABLE user_certificate');
        $this->addSql('DROP TABLE user_certificate_security_zone');
        $this->addSql('DROP INDEX IDX_CF2713FD55B127A4');
        $this->addSql('CREATE TEMPORARY TABLE __temp__host AS SELECT id, added_by_id, display_name, fully_qualified_name, ipv4_address, ipv6_address, last_contacted_at, created_at, updated_at FROM host');
        $this->addSql('DROP TABLE host');
        $this->addSql('CREATE TABLE host (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, added_by_id INTEGER NOT NULL, display_name VARCHAR(255) NOT NULL, fully_qualified_name VARCHAR(255) NOT NULL, ipv4_address BLOB DEFAULT NULL --(DC2Type:ip)
        , ipv6_address BLOB DEFAULT NULL --(DC2Type:ip)
        , last_contacted_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        )');
        $this->addSql('INSERT INTO host (id, added_by_id, display_name, fully_qualified_name, ipv4_address, ipv6_address, last_contacted_at, created_at, updated_at) SELECT id, added_by_id, display_name, fully_qualified_name, ipv4_address, ipv6_address, last_contacted_at, created_at, updated_at FROM __temp__host');
        $this->addSql('DROP TABLE __temp__host');
        $this->addSql('CREATE INDEX IDX_CF2713FD55B127A4 ON host (added_by_id)');
        $this->addSql('DROP INDEX IDX_597900C11FB8D185');
        $this->addSql('DROP INDEX IDX_597900C1F2C734B6');
        $this->addSql('CREATE TEMPORARY TABLE __temp__host_security_zone AS SELECT host_id, security_zone_id FROM host_security_zone');
        $this->addSql('DROP TABLE host_security_zone');
        $this->addSql('CREATE TABLE host_security_zone (host_id INTEGER NOT NULL, security_zone_id INTEGER NOT NULL, PRIMARY KEY(host_id, security_zone_id))');
        $this->addSql('INSERT INTO host_security_zone (host_id, security_zone_id) SELECT host_id, security_zone_id FROM __temp__host_security_zone');
        $this->addSql('DROP TABLE __temp__host_security_zone');
        $this->addSql('CREATE INDEX IDX_597900C11FB8D185 ON host_security_zone (host_id)');
        $this->addSql('CREATE INDEX IDX_597900C1F2C734B6 ON host_security_zone (security_zone_id)');
        $this->addSql('DROP INDEX IDX_9174E196A76ED395');
        $this->addSql('DROP INDEX IDX_9174E196F2C734B6');
        $this->addSql('CREATE TEMPORARY TABLE __temp__user_security_zone AS SELECT user_id, security_zone_id FROM user_security_zone');
        $this->addSql('DROP TABLE user_security_zone');
        $this->addSql('CREATE TABLE user_security_zone (user_id INTEGER NOT NULL, security_zone_id INTEGER NOT NULL, PRIMARY KEY(user_id, security_zone_id))');
        $this->addSql('INSERT INTO user_security_zone (user_id, security_zone_id) SELECT user_id, security_zone_id FROM __temp__user_security_zone');
        $this->addSql('DROP TABLE __temp__user_security_zone');
        $this->addSql('CREATE INDEX IDX_9174E196A76ED395 ON user_security_zone (user_id)');
        $this->addSql('CREATE INDEX IDX_9174E196F2C734B6 ON user_security_zone (security_zone_id)');
    }
}
