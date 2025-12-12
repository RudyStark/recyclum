<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251207173819 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE quick_booking_id_seq CASCADE');
        $this->addSql('DROP TABLE quick_booking');
        $this->addSql('ALTER TABLE buyback_appointment DROP CONSTRAINT FK_6A529F964C22EE2C');
        $this->addSql('ALTER TABLE buyback_appointment ADD notes TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE buyback_appointment ADD CONSTRAINT FK_6A529F964C22EE2C FOREIGN KEY (buyback_request_id) REFERENCES buyback_request (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE buyback_request DROP payment_deadline');
        $this->addSql('ALTER TABLE buyback_request DROP appointment_type');
        $this->addSql('ALTER TABLE buyback_request ALTER appointment_token TYPE VARCHAR(64)');
        $this->addSql('ALTER TABLE buyback_request RENAME COLUMN notes TO admin_notes');
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
        $this->addSql('CREATE SEQUENCE quick_booking_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE quick_booking (id INT NOT NULL, name VARCHAR(100) NOT NULL, phone VARCHAR(20) NOT NULL, zip VARCHAR(10) NOT NULL, service VARCHAR(50) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN quick_booking.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE buyback_request ADD payment_deadline TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE buyback_request ADD appointment_type VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE buyback_request ALTER appointment_token TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE buyback_request RENAME COLUMN admin_notes TO notes');
        $this->addSql('COMMENT ON COLUMN buyback_request.payment_deadline IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE buyback_appointment DROP CONSTRAINT fk_6a529f964c22ee2c');
        $this->addSql('ALTER TABLE buyback_appointment DROP notes');
        $this->addSql('ALTER TABLE buyback_appointment ADD CONSTRAINT fk_6a529f964c22ee2c FOREIGN KEY (buyback_request_id) REFERENCES buyback_request (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
