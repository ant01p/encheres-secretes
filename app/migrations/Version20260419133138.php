<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260419133138 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE item ADD final_price DOUBLE PRECISION NOT NULL, ADD winner_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE item ADD CONSTRAINT FK_1F1B251E5DFCD4B8 FOREIGN KEY (winner_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_1F1B251E5DFCD4B8 ON item (winner_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE item DROP FOREIGN KEY FK_1F1B251E5DFCD4B8');
        $this->addSql('DROP INDEX IDX_1F1B251E5DFCD4B8 ON item');
        $this->addSql('ALTER TABLE item DROP final_price, DROP winner_id');
    }
}
