<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251204091126 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE appliance_model (id SERIAL NOT NULL, category VARCHAR(100) NOT NULL, brand VARCHAR(100) NOT NULL, model_reference VARCHAR(255) NOT NULL, model_name VARCHAR(255) NOT NULL, price_new INT NOT NULL, release_year INT NOT NULL, tier VARCHAR(50) NOT NULL, capacity VARCHAR(100) DEFAULT NULL, energy_class VARCHAR(10) DEFAULT NULL, features JSON NOT NULL, is_active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BB4D159D976A2080 ON appliance_model (model_reference)');
        $this->addSql('CREATE INDEX idx_category ON appliance_model (category)');
        $this->addSql('CREATE INDEX idx_brand ON appliance_model (brand)');
        $this->addSql('CREATE INDEX idx_model_reference ON appliance_model (model_reference)');
        $this->addSql('COMMENT ON COLUMN appliance_model.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN appliance_model.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE buyback_request DROP serial_number');
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
        $this->addSql('DROP TABLE appliance_model');
        $this->addSql('ALTER TABLE buyback_request ADD serial_number VARCHAR(255) DEFAULT NULL');
    }
}
