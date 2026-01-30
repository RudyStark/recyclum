<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260129132320 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE contact_attachment_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE contact_attachment (id INT NOT NULL, contact_reply_id INT DEFAULT NULL, contact_message_id INT DEFAULT NULL, original_filename VARCHAR(255) NOT NULL, stored_filename VARCHAR(255) NOT NULL, mime_type VARCHAR(100) NOT NULL, file_size INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_BEE6802C102BD2E8 ON contact_attachment (contact_reply_id)');
        $this->addSql('CREATE INDEX IDX_BEE6802C94C34ABE ON contact_attachment (contact_message_id)');
        $this->addSql('COMMENT ON COLUMN contact_attachment.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE contact_attachment ADD CONSTRAINT FK_BEE6802C102BD2E8 FOREIGN KEY (contact_reply_id) REFERENCES contact_reply (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE contact_attachment ADD CONSTRAINT FK_BEE6802C94C34ABE FOREIGN KEY (contact_message_id) REFERENCES contact_message (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
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
        $this->addSql('DROP SEQUENCE contact_attachment_id_seq CASCADE');
        $this->addSql('ALTER TABLE contact_attachment DROP CONSTRAINT FK_BEE6802C102BD2E8');
        $this->addSql('ALTER TABLE contact_attachment DROP CONSTRAINT FK_BEE6802C94C34ABE');
        $this->addSql('DROP TABLE contact_attachment');
    }
}
