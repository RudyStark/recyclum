<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260129084105 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Add column as nullable first
        $this->addSql('ALTER TABLE contact_message ADD reply_token VARCHAR(64) DEFAULT NULL');

        // Generate tokens for existing rows using md5 of id + timestamp + random()
        $this->addSql("UPDATE contact_message SET reply_token = md5(id::text || now()::text || random()::text) || md5(random()::text) WHERE reply_token IS NULL");

        // Now make it NOT NULL
        $this->addSql('ALTER TABLE contact_message ALTER COLUMN reply_token SET NOT NULL');

        // Add unique index
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2C9211FE90DD9A37 ON contact_message (reply_token)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA pgbouncer');
        $this->addSql('CREATE SCHEMA realtime');
        $this->addSql('CREATE SCHEMA extensions');
        $this->addSql('CREATE SCHEMA vault');
        $this->addSql('CREATE SCHEMA graphql_public');
        $this->addSql('CREATE SCHEMA graphql');
        $this->addSql('CREATE SCHEMA auth');
        $this->addSql('CREATE SCHEMA storage');
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP INDEX UNIQ_2C9211FE90DD9A37');
        $this->addSql('ALTER TABLE contact_message DROP reply_token');
    }
}
