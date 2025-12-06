<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251204150259 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE appliance_model DROP price_new');
        $this->addSql('ALTER TABLE appliance_model DROP capacity');
        $this->addSql('ALTER TABLE appliance_model DROP energy_class');
        $this->addSql('ALTER TABLE appliance_model DROP features');
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
        $this->addSql('ALTER TABLE appliance_model ADD price_new INT NOT NULL');
        $this->addSql('ALTER TABLE appliance_model ADD capacity VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE appliance_model ADD energy_class VARCHAR(10) DEFAULT NULL');
        $this->addSql('ALTER TABLE appliance_model ADD features JSON NOT NULL');
    }
}
