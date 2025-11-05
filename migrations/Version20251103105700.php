<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251103105700 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product ALTER is_published SET DEFAULT false');
        $this->addSql('ALTER TABLE product_image DROP CONSTRAINT FK_64617F034584665A');
        $this->addSql('ALTER TABLE product_image ADD size INT DEFAULT NULL');
        $this->addSql('ALTER TABLE product_image ADD mime_type VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE product_image ADD is_main BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE product_image ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE product_image DROP path');
        $this->addSql('ALTER TABLE product_image ALTER "position" TYPE INT');
        $this->addSql('ALTER TABLE product_image RENAME COLUMN alt TO filename');
        $this->addSql('COMMENT ON COLUMN product_image.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE product_image ADD CONSTRAINT FK_64617F034584665A FOREIGN KEY (product_id) REFERENCES product (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
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
        $this->addSql('ALTER TABLE product_image ADD path VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE product_image DROP size');
        $this->addSql('ALTER TABLE product_image DROP mime_type');
        $this->addSql('ALTER TABLE product_image DROP is_main');
        $this->addSql('ALTER TABLE product_image DROP updated_at');
        $this->addSql('ALTER TABLE product_image ALTER position TYPE SMALLINT');
        $this->addSql('ALTER TABLE product_image RENAME COLUMN filename TO alt');
        $this->addSql('ALTER TABLE product_image ADD CONSTRAINT fk_64617f034584665a FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE product ALTER is_published DROP DEFAULT');
    }
}
