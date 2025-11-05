<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251104083437 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_image DROP CONSTRAINT FK_64617F034584665A');
        $this->addSql('ALTER TABLE product_image ADD alt VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE product_image ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL');
        $this->addSql('COMMENT ON COLUMN product_image.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE product_image ADD CONSTRAINT FK_64617F034584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
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
        $this->addSql('ALTER TABLE product_image DROP CONSTRAINT fk_64617f034584665a');
        $this->addSql('ALTER TABLE product_image DROP alt');
        $this->addSql('ALTER TABLE product_image DROP created_at');
        $this->addSql('ALTER TABLE product_image ADD CONSTRAINT fk_64617f034584665a FOREIGN KEY (product_id) REFERENCES product (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
