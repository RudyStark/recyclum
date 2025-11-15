<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251114194636 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE buyback_request (id SERIAL NOT NULL, category VARCHAR(100) NOT NULL, brand VARCHAR(100) NOT NULL, model VARCHAR(255) DEFAULT NULL, purchase_year VARCHAR(50) NOT NULL, has_invoice BOOLEAN NOT NULL, functional_state VARCHAR(50) NOT NULL, aesthetic_state VARCHAR(50) NOT NULL, has_all_accessories BOOLEAN NOT NULL, additional_comments TEXT DEFAULT NULL, photos JSON NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, email VARCHAR(255) NOT NULL, phone VARCHAR(20) NOT NULL, address VARCHAR(255) NOT NULL, zip_code VARCHAR(10) NOT NULL, city VARCHAR(100) NOT NULL, floor VARCHAR(100) DEFAULT NULL, has_elevator BOOLEAN NOT NULL, payment_method VARCHAR(20) NOT NULL, iban VARCHAR(34) DEFAULT NULL, account_holder VARCHAR(255) DEFAULT NULL, preferred_date DATE DEFAULT NULL, time_slots JSON NOT NULL, estimated_price_min INT DEFAULT NULL, estimated_price_max INT DEFAULT NULL, calculation_details JSON DEFAULT NULL, status VARCHAR(50) NOT NULL, final_price INT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN buyback_request.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN buyback_request.updated_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA storage');
        $this->addSql('CREATE SCHEMA auth');
        $this->addSql('CREATE SCHEMA graphql');
        $this->addSql('CREATE SCHEMA graphql_public');
        $this->addSql('CREATE SCHEMA vault');
        $this->addSql('CREATE SCHEMA realtime');
        $this->addSql('CREATE SCHEMA pgbouncer');
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE SCHEMA extensions');
        $this->addSql('DROP TABLE buyback_request');
    }
}
