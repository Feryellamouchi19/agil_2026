<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260729200923 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE `guichets` (id INT AUTO_INCREMENT NOT NULL, numero VARCHAR(10) NOT NULL, statut VARCHAR(20) NOT NULL, type_service_id INT DEFAULT NULL, agiliste_id INT DEFAULT NULL, INDEX IDX_C2E4901BF05F7FC3 (type_service_id), INDEX IDX_C2E4901BB4504E53 (agiliste_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `services` (id INT AUTO_INCREMENT NOT NULL, nom_service VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, duree_moyenne INT DEFAULT 5 NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `tickets` (id INT AUTO_INCREMENT NOT NULL, numero_ticket VARCHAR(20) NOT NULL, qr_code VARCHAR(255) DEFAULT NULL, heure_creation DATETIME NOT NULL, heure_appel DATETIME DEFAULT NULL, heure_fin DATETIME DEFAULT NULL, temps_estime INT NOT NULL, statut VARCHAR(30) NOT NULL, client_id INT NOT NULL, service_id INT NOT NULL, guichet_id INT DEFAULT NULL, INDEX IDX_54469DF419EB6921 (client_id), INDEX IDX_54469DF4ED5CA9E6 (service_id), INDEX IDX_54469DF4D75742EE (guichet_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE `guichets` ADD CONSTRAINT FK_C2E4901BF05F7FC3 FOREIGN KEY (type_service_id) REFERENCES `services` (id)');
        $this->addSql('ALTER TABLE `guichets` ADD CONSTRAINT FK_C2E4901BB4504E53 FOREIGN KEY (agiliste_id) REFERENCES `users` (id)');
        $this->addSql('ALTER TABLE `tickets` ADD CONSTRAINT FK_54469DF419EB6921 FOREIGN KEY (client_id) REFERENCES `users` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE `tickets` ADD CONSTRAINT FK_54469DF4ED5CA9E6 FOREIGN KEY (service_id) REFERENCES `services` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE `tickets` ADD CONSTRAINT FK_54469DF4D75742EE FOREIGN KEY (guichet_id) REFERENCES `guichets` (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `guichets` DROP FOREIGN KEY FK_C2E4901BF05F7FC3');
        $this->addSql('ALTER TABLE `guichets` DROP FOREIGN KEY FK_C2E4901BB4504E53');
        $this->addSql('ALTER TABLE `tickets` DROP FOREIGN KEY FK_54469DF419EB6921');
        $this->addSql('ALTER TABLE `tickets` DROP FOREIGN KEY FK_54469DF4ED5CA9E6');
        $this->addSql('ALTER TABLE `tickets` DROP FOREIGN KEY FK_54469DF4D75742EE');
        $this->addSql('DROP TABLE `guichets`');
        $this->addSql('DROP TABLE `services`');
        $this->addSql('DROP TABLE `tickets`');
    }
}
