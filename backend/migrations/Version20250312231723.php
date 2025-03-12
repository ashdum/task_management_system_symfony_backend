<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250312231723 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE dashboard_users (id UUID NOT NULL, user_id UUID NOT NULL, dashboard_id UUID NOT NULL, role VARCHAR(255) NOT NULL, del_status VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_dashboard_users_user_id ON dashboard_users (user_id)');
        $this->addSql('CREATE INDEX idx_dashboard_users_dashboard_id ON dashboard_users (dashboard_id)');
        $this->addSql('COMMENT ON COLUMN dashboard_users.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN dashboard_users.user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN dashboard_users.dashboard_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE dashboards (id UUID NOT NULL, title VARCHAR(255) NOT NULL, owner_ids JSON NOT NULL, background VARCHAR(255) DEFAULT NULL, description TEXT DEFAULT NULL, is_public BOOLEAN DEFAULT false NOT NULL, settings JSON DEFAULT NULL, del_status INT NOT NULL, del_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN dashboards.id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE dashboard_users ADD CONSTRAINT FK_FA9AB4AAA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE dashboard_users ADD CONSTRAINT FK_FA9AB4AAB9D04D2B FOREIGN KEY (dashboard_id) REFERENCES dashboards (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE dashboard DROP CONSTRAINT fk_5c94fff861220ea6');
        $this->addSql('DROP TABLE dashboard');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE TABLE dashboard (id UUID NOT NULL, creator_id UUID NOT NULL, title VARCHAR(255) NOT NULL, del_status INT NOT NULL, del_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_5c94fff861220ea6 ON dashboard (creator_id)');
        $this->addSql('COMMENT ON COLUMN dashboard.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN dashboard.creator_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE dashboard ADD CONSTRAINT fk_5c94fff861220ea6 FOREIGN KEY (creator_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE dashboard_users DROP CONSTRAINT FK_FA9AB4AAA76ED395');
        $this->addSql('ALTER TABLE dashboard_users DROP CONSTRAINT FK_FA9AB4AAB9D04D2B');
        $this->addSql('DROP TABLE dashboard_users');
        $this->addSql('DROP TABLE dashboards');
    }
}
