<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251113184916 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE quick_booking_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE quick_booking (id INT NOT NULL, name VARCHAR(100) NOT NULL, phone VARCHAR(20) NOT NULL, zip VARCHAR(10) NOT NULL, service VARCHAR(50) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN quick_booking.created_at IS \'(DC2Type:datetime_immutable)\'');
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
        $this->addSql('DROP SEQUENCE quick_booking_id_seq CASCADE');
        $this->addSql('DROP TABLE quick_booking');
    }
}
