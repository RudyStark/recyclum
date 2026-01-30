<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260129080500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE contact_message_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE contact_reply_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE contact_message (id INT NOT NULL, user_id INT DEFAULT NULL, related_order_id INT DEFAULT NULL, related_order_item_id INT DEFAULT NULL, ticket_number VARCHAR(50) NOT NULL, subject VARCHAR(30) NOT NULL, status VARCHAR(30) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, email VARCHAR(180) NOT NULL, phone VARCHAR(20) DEFAULT NULL, message TEXT NOT NULL, admin_notes TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, read_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, closed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2C9211FEECD2759F ON contact_message (ticket_number)');
        $this->addSql('CREATE INDEX IDX_2C9211FEA76ED395 ON contact_message (user_id)');
        $this->addSql('CREATE INDEX IDX_2C9211FE2B1C2395 ON contact_message (related_order_id)');
        $this->addSql('CREATE INDEX IDX_2C9211FEC47318A ON contact_message (related_order_item_id)');
        $this->addSql('COMMENT ON COLUMN contact_message.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN contact_message.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN contact_message.read_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN contact_message.closed_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE contact_reply (id INT NOT NULL, contact_message_id INT NOT NULL, content TEXT NOT NULL, replied_by VARCHAR(100) DEFAULT NULL, is_admin_reply BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_D082D6EF94C34ABE ON contact_reply (contact_message_id)');
        $this->addSql('COMMENT ON COLUMN contact_reply.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE contact_message ADD CONSTRAINT FK_2C9211FEA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE contact_message ADD CONSTRAINT FK_2C9211FE2B1C2395 FOREIGN KEY (related_order_id) REFERENCES "order" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE contact_message ADD CONSTRAINT FK_2C9211FEC47318A FOREIGN KEY (related_order_item_id) REFERENCES order_item (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE contact_reply ADD CONSTRAINT FK_D082D6EF94C34ABE FOREIGN KEY (contact_message_id) REFERENCES contact_message (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
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
        $this->addSql('DROP SEQUENCE contact_message_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE contact_reply_id_seq CASCADE');
        $this->addSql('ALTER TABLE contact_message DROP CONSTRAINT FK_2C9211FEA76ED395');
        $this->addSql('ALTER TABLE contact_message DROP CONSTRAINT FK_2C9211FE2B1C2395');
        $this->addSql('ALTER TABLE contact_message DROP CONSTRAINT FK_2C9211FEC47318A');
        $this->addSql('ALTER TABLE contact_reply DROP CONSTRAINT FK_D082D6EF94C34ABE');
        $this->addSql('DROP TABLE contact_message');
        $this->addSql('DROP TABLE contact_reply');
    }
}
