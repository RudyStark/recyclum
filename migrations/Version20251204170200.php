<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251204170200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migration BuybackRequest vers nouveau schéma avec migration de données';
    }

    public function up(Schema $schema): void
    {
        // 1. Ajouter le champ serialNumber
        $this->addSql('ALTER TABLE buyback_request ADD serial_number VARCHAR(100) DEFAULT NULL');

        // 2. Renommer zipCode en postalCode
        $this->addSql('ALTER TABLE buyback_request RENAME COLUMN zip_code TO postal_code');

        // 3. Ajouter les nouvelles colonnes en NULLABLE d'abord
        $this->addSql('ALTER TABLE buyback_request ADD functional_condition VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE buyback_request ADD aesthetic_condition VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE buyback_request ADD defects_description TEXT DEFAULT NULL');

        // Photos en TEXT pour supporter les chemins longs ou base64
        $this->addSql('ALTER TABLE buyback_request ADD photo1 TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE buyback_request ADD photo2 TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE buyback_request ADD photo3 TEXT DEFAULT NULL');

        $this->addSql('ALTER TABLE buyback_request ADD estimated_price INT DEFAULT NULL');
        $this->addSql('ALTER TABLE buyback_request ADD notes TEXT DEFAULT NULL');

        // 4. Migrer les données existantes
        $this->addSql("
            UPDATE buyback_request
            SET
                functional_condition = CASE
                    WHEN functional_state = 'parfait' THEN 'perfect'
                    WHEN functional_state = 'panne-legere' THEN 'minor_issues'
                    WHEN functional_state = 'hors-service' THEN 'not_working'
                    WHEN functional_state = 'pieces' THEN 'not_working'
                    ELSE 'working'
                END,
                aesthetic_condition = CASE
                    WHEN aesthetic_state = 'tres-bon' THEN 'excellent'
                    WHEN aesthetic_state = 'bon' THEN 'good'
                    WHEN aesthetic_state = 'usage' THEN 'fair'
                    WHEN aesthetic_state = 'tres-usage' THEN 'poor'
                    ELSE 'good'
                END,
                defects_description = additional_comments,
                notes = additional_comments,
                estimated_price = CASE
                    WHEN estimated_price_min IS NOT NULL AND estimated_price_max IS NOT NULL
                    THEN (estimated_price_min + estimated_price_max) / 2
                    ELSE NULL
                END
            WHERE functional_condition IS NULL
        ");

        // 5. Migrer les photos depuis JSON (avec gestion sécurisée)
        $this->addSql("
            UPDATE buyback_request
            SET
                photo1 = CASE
                    WHEN photos::text != '[]' AND jsonb_array_length(photos::jsonb) > 0
                    THEN photos::jsonb->>0
                    ELSE NULL
                END,
                photo2 = CASE
                    WHEN photos::text != '[]' AND jsonb_array_length(photos::jsonb) > 1
                    THEN photos::jsonb->>1
                    ELSE NULL
                END,
                photo3 = CASE
                    WHEN photos::text != '[]' AND jsonb_array_length(photos::jsonb) > 2
                    THEN photos::jsonb->>2
                    ELSE NULL
                END
            WHERE photos IS NOT NULL
        ");

        // 6. Transformer purchaseYear en INT
        $this->addSql('ALTER TABLE buyback_request ADD purchase_year_int INT DEFAULT NULL');
        $this->addSql("
            UPDATE buyback_request
            SET purchase_year_int = CASE
                WHEN purchase_year ~ '^[0-9]{4}$' THEN purchase_year::int
                WHEN purchase_year ~ '^[0-9]{4}-[0-9]{4}$' THEN SPLIT_PART(purchase_year, '-', 1)::int
                ELSE EXTRACT(YEAR FROM NOW())::int - 5
            END
        ");

        // 7. Rendre les colonnes NOT NULL maintenant qu'elles ont des valeurs
        $this->addSql('ALTER TABLE buyback_request ALTER COLUMN functional_condition SET NOT NULL');
        $this->addSql('ALTER TABLE buyback_request ALTER COLUMN aesthetic_condition SET NOT NULL');
        $this->addSql('ALTER TABLE buyback_request ALTER COLUMN purchase_year_int SET NOT NULL');

        // 8. Supprimer les anciennes colonnes
        $this->addSql('ALTER TABLE buyback_request DROP COLUMN IF EXISTS functional_state');
        $this->addSql('ALTER TABLE buyback_request DROP COLUMN IF EXISTS aesthetic_state');
        $this->addSql('ALTER TABLE buyback_request DROP COLUMN IF EXISTS additional_comments');
        $this->addSql('ALTER TABLE buyback_request DROP COLUMN IF EXISTS photos');
        $this->addSql('ALTER TABLE buyback_request DROP COLUMN IF EXISTS estimated_price_min');
        $this->addSql('ALTER TABLE buyback_request DROP COLUMN IF EXISTS estimated_price_max');
        $this->addSql('ALTER TABLE buyback_request DROP COLUMN IF EXISTS calculation_details');
        $this->addSql('ALTER TABLE buyback_request DROP COLUMN IF EXISTS floor');
        $this->addSql('ALTER TABLE buyback_request DROP COLUMN IF EXISTS has_elevator');
        $this->addSql('ALTER TABLE buyback_request DROP COLUMN IF EXISTS account_holder');
        $this->addSql('ALTER TABLE buyback_request DROP COLUMN IF EXISTS preferred_date');
        $this->addSql('ALTER TABLE buyback_request DROP COLUMN IF EXISTS time_slots');
        $this->addSql('ALTER TABLE buyback_request DROP COLUMN IF EXISTS purchase_year');

        // 9. Renommer purchase_year_int en purchase_year
        $this->addSql('ALTER TABLE buyback_request RENAME COLUMN purchase_year_int TO purchase_year');
    }

    public function down(Schema $schema): void
    {
        // Rollback si nécessaire
        $this->addSql('ALTER TABLE buyback_request RENAME COLUMN postal_code TO zip_code');
        $this->addSql('ALTER TABLE buyback_request DROP COLUMN IF EXISTS serial_number');
        $this->addSql('ALTER TABLE buyback_request DROP COLUMN IF EXISTS functional_condition');
        $this->addSql('ALTER TABLE buyback_request DROP COLUMN IF EXISTS aesthetic_condition');
        $this->addSql('ALTER TABLE buyback_request DROP COLUMN IF EXISTS defects_description');
        $this->addSql('ALTER TABLE buyback_request DROP COLUMN IF EXISTS photo1');
        $this->addSql('ALTER TABLE buyback_request DROP COLUMN IF EXISTS photo2');
        $this->addSql('ALTER TABLE buyback_request DROP COLUMN IF EXISTS photo3');
        $this->addSql('ALTER TABLE buyback_request DROP COLUMN IF EXISTS estimated_price');
        $this->addSql('ALTER TABLE buyback_request DROP COLUMN IF EXISTS notes');

        // Recréer les anciennes colonnes
        $this->addSql('ALTER TABLE buyback_request ADD functional_state VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE buyback_request ADD aesthetic_state VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE buyback_request ADD additional_comments TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE buyback_request ADD photos JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE buyback_request ADD estimated_price_min INT DEFAULT NULL');
        $this->addSql('ALTER TABLE buyback_request ADD estimated_price_max INT DEFAULT NULL');
        $this->addSql('ALTER TABLE buyback_request ADD calculation_details JSON DEFAULT NULL');
    }
}
