<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251118123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add User indexes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX UserUsernameIdx ON "user" (username)');
        $this->addSql('CREATE INDEX UserEmailIdx ON "user" (email)');
        $this->addSql('CREATE INDEX UserCreatedAtIdx ON "user" (created_at)');

        $this->addSql('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        $this->addSql('CREATE INDEX UserUsernameTrgmIdx ON "user" USING GIN (username gin_trgm_ops)');
        $this->addSql('CREATE INDEX UserEmailTrgmIdx ON "user" USING GIN (email gin_trgm_ops)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UserUsernameTrgmIdx');
        $this->addSql('DROP INDEX UserEmailTrgmIdx');

        $this->addSql('DROP INDEX UserUsernameIdx');
        $this->addSql('DROP INDEX UserEmailIdx');
        $this->addSql('DROP INDEX UserCreatedAtIdx');
    }
}
