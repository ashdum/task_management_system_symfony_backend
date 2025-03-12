<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250312214037 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE dashboard (id UUID NOT NULL, creator_id UUID NOT NULL, title VARCHAR(255) NOT NULL, del_status INT NOT NULL, del_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_5C94FFF861220EA6 ON dashboard (creator_id)');
        $this->addSql('COMMENT ON COLUMN dashboard.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN dashboard.creator_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE dashboard ADD CONSTRAINT FK_5C94FFF861220EA6 FOREIGN KEY (creator_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE dashboard DROP CONSTRAINT FK_5C94FFF861220EA6');
        $this->addSql('DROP TABLE dashboard');
    }
}
