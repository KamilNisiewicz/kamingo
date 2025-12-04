<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251204134454 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE session_completion (id INT AUTO_INCREMENT NOT NULL, category VARCHAR(50) NOT NULL, completed_date DATE NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_D8403C25A76ED395 (user_id), UNIQUE INDEX unique_user_category_date (user_id, category, completed_date), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE session_completion ADD CONSTRAINT FK_D8403C25A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE session_completion DROP FOREIGN KEY FK_D8403C25A76ED395');
        $this->addSql('DROP TABLE session_completion');
    }
}
