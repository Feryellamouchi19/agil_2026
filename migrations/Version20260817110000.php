<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260817110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add `type` column to services table for loyalty system.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE services ADD type VARCHAR(30) NOT NULL DEFAULT "AUTRE"');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE services DROP type');
    }
}
?>
