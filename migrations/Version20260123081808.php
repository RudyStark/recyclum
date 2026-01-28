<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260123081808 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add DeliveryAppointment entity and delivery_booking_token to Order';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE delivery_appointment_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE delivery_appointment (id INT NOT NULL, order_id INT NOT NULL, appointment_date DATE NOT NULL, status VARCHAR(50) NOT NULL, notes TEXT DEFAULT NULL, booked_by VARCHAR(20) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C22559028D9F6D38 ON delivery_appointment (order_id)');
        $this->addSql('COMMENT ON COLUMN delivery_appointment.appointment_date IS \'(DC2Type:date_immutable)\'');
        $this->addSql('COMMENT ON COLUMN delivery_appointment.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN delivery_appointment.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE delivery_appointment ADD CONSTRAINT FK_C22559028D9F6D38 FOREIGN KEY (order_id) REFERENCES "order" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE "order" ADD delivery_booking_token VARCHAR(32) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F5299398C9B91B6E ON "order" (delivery_booking_token)');
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
        $this->addSql('DROP SEQUENCE delivery_appointment_id_seq CASCADE');
        $this->addSql('ALTER TABLE delivery_appointment DROP CONSTRAINT FK_C22559028D9F6D38');
        $this->addSql('DROP TABLE delivery_appointment');
        $this->addSql('DROP INDEX UNIQ_F5299398C9B91B6E');
        $this->addSql('ALTER TABLE "order" DROP delivery_booking_token');
    }
}
