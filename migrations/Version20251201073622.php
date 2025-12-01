<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251201073622 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_progress (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(255) NOT NULL, next_review_date DATETIME NOT NULL, repetitions INT NOT NULL, last_reviewed_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, word_id INT NOT NULL, INDEX IDX_C28C1646A76ED395 (user_id), INDEX IDX_C28C1646E357438D (word_id), UNIQUE INDEX UNIQ_USER_WORD (user_id, word_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE word (id INT AUTO_INCREMENT NOT NULL, word VARCHAR(255) NOT NULL, translation VARCHAR(255) NOT NULL, example LONGTEXT DEFAULT NULL, category VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE user_progress ADD CONSTRAINT FK_C28C1646A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE user_progress ADD CONSTRAINT FK_C28C1646E357438D FOREIGN KEY (word_id) REFERENCES word (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_progress DROP FOREIGN KEY FK_C28C1646A76ED395');
        $this->addSql('ALTER TABLE user_progress DROP FOREIGN KEY FK_C28C1646E357438D');
        $this->addSql('DROP TABLE user_progress');
        $this->addSql('DROP TABLE word');
    }
}
