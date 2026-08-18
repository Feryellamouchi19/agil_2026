<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260817084523 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE `point_transactions` (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(255) NOT NULL, value DOUBLE PRECISION NOT NULL, points INT NOT NULL, created_at DATETIME NOT NULL, balance_after INT NOT NULL, user_id INT NOT NULL, ticket_id INT DEFAULT NULL, voucher_id INT DEFAULT NULL, INDEX IDX_1C27E328A76ED395 (user_id), INDEX IDX_1C27E328700047D2 (ticket_id), INDEX IDX_1C27E32828AA1B6F (voucher_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `vouchers` (id INT AUTO_INCREMENT NOT NULL, value DOUBLE PRECISION NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, used_at DATETIME DEFAULT NULL, user_id INT NOT NULL, INDEX IDX_93150748A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE `point_transactions` ADD CONSTRAINT FK_1C27E328A76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE `point_transactions` ADD CONSTRAINT FK_1C27E328700047D2 FOREIGN KEY (ticket_id) REFERENCES `tickets` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `point_transactions` ADD CONSTRAINT FK_1C27E32828AA1B6F FOREIGN KEY (voucher_id) REFERENCES `vouchers` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `vouchers` ADD CONSTRAINT FK_93150748A76ED395 FOREIGN KEY (user_id) REFERENCES `users` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE services CHANGE type type VARCHAR(30) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `point_transactions` DROP FOREIGN KEY FK_1C27E328A76ED395');
        $this->addSql('ALTER TABLE `point_transactions` DROP FOREIGN KEY FK_1C27E328700047D2');
        $this->addSql('ALTER TABLE `point_transactions` DROP FOREIGN KEY FK_1C27E32828AA1B6F');
        $this->addSql('ALTER TABLE `vouchers` DROP FOREIGN KEY FK_93150748A76ED395');
        $this->addSql('DROP TABLE `point_transactions`');
        $this->addSql('DROP TABLE `vouchers`');
        $this->addSql('ALTER TABLE `services` CHANGE type type VARCHAR(30) DEFAULT \'AUTRE\' NOT NULL');
    }
}
