<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251113183107 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE repair_request_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE repair_request (id INT NOT NULL, category VARCHAR(100) NOT NULL, brand VARCHAR(100) DEFAULT NULL, model VARCHAR(100) DEFAULT NULL, issue VARCHAR(255) NOT NULL, issue_details TEXT NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, email VARCHAR(180) NOT NULL, phone VARCHAR(20) NOT NULL, address VARCHAR(255) DEFAULT NULL, zip_code VARCHAR(10) DEFAULT NULL, city VARCHAR(100) DEFAULT NULL, preferred_date DATE DEFAULT NULL, repair_location VARCHAR(20) NOT NULL, urgency BOOLEAN NOT NULL, status VARCHAR(50) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN repair_request.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN repair_request.updated_at IS \'(DC2Type:datetime_immutable)\'');
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
        $this->addSql('DROP SEQUENCE repair_request_id_seq CASCADE');
        $this->addSql('DROP TABLE repair_request');
    }
}
