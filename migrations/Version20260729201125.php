<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260729201125 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE `historique_connexion` (id INT AUTO_INCREMENT NOT NULL, adresse_ip VARCHAR(45) DEFAULT NULL, navigateur LONGTEXT DEFAULT NULL, date_connexion DATETIME NOT NULL, utilisateur_id INT NOT NULL, INDEX IDX_C018B2D4FB88E14F (utilisateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `notifications` (id INT AUTO_INCREMENT NOT NULL, message LONGTEXT NOT NULL, lu TINYINT DEFAULT 0 NOT NULL, date_notif DATETIME NOT NULL, utilisateur_id INT NOT NULL, INDEX IDX_6000B0D3FB88E14F (utilisateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `reclamations` (id INT AUTO_INCREMENT NOT NULL, sujet VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, reponse LONGTEXT DEFAULT NULL, date_creation DATETIME NOT NULL, statut VARCHAR(30) NOT NULL, client_id INT NOT NULL, INDEX IDX_1CAD6B7619EB6921 (client_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `rendez_vous` (id INT AUTO_INCREMENT NOT NULL, date_rv DATE NOT NULL, heure_rv TIME NOT NULL, sujet VARCHAR(255) NOT NULL, commentaire LONGTEXT DEFAULT NULL, statut VARCHAR(30) NOT NULL, client_id INT NOT NULL, gerant_id INT DEFAULT NULL, INDEX IDX_65E8AA0A19EB6921 (client_id), INDEX IDX_65E8AA0AA500A924 (gerant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE `historique_connexion` ADD CONSTRAINT FK_C018B2D4FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES `users` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE `notifications` ADD CONSTRAINT FK_6000B0D3FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES `users` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE `reclamations` ADD CONSTRAINT FK_1CAD6B7619EB6921 FOREIGN KEY (client_id) REFERENCES `users` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE `rendez_vous` ADD CONSTRAINT FK_65E8AA0A19EB6921 FOREIGN KEY (client_id) REFERENCES `users` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE `rendez_vous` ADD CONSTRAINT FK_65E8AA0AA500A924 FOREIGN KEY (gerant_id) REFERENCES `users` (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `historique_connexion` DROP FOREIGN KEY FK_C018B2D4FB88E14F');
        $this->addSql('ALTER TABLE `notifications` DROP FOREIGN KEY FK_6000B0D3FB88E14F');
        $this->addSql('ALTER TABLE `reclamations` DROP FOREIGN KEY FK_1CAD6B7619EB6921');
        $this->addSql('ALTER TABLE `rendez_vous` DROP FOREIGN KEY FK_65E8AA0A19EB6921');
        $this->addSql('ALTER TABLE `rendez_vous` DROP FOREIGN KEY FK_65E8AA0AA500A924');
        $this->addSql('DROP TABLE `historique_connexion`');
        $this->addSql('DROP TABLE `notifications`');
        $this->addSql('DROP TABLE `reclamations`');
        $this->addSql('DROP TABLE `rendez_vous`');
    }
}
