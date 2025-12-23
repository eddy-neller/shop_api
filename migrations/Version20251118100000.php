<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251118100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Shop Category & Product indexes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX ShopProductCreatedAtIdx ON shop_product (created_at)');
        $this->addSql('CREATE INDEX ShopProductPriceIdx ON shop_product (price)');
        $this->addSql('CREATE INDEX ShopProductTitleIdx ON shop_product (title)');

        $this->addSql('CREATE INDEX ShopCategoryNbProductIdx ON shop_category (nb_product)');
        $this->addSql('CREATE INDEX ShopCategoryLevelIdx ON shop_category (lvl)');
        $this->addSql('CREATE INDEX ShopCategoryCreatedAtIdx ON shop_category (created_at)');
        $this->addSql('CREATE INDEX ShopCategoryTitleIdx ON shop_category (title)');

        $this->addSql('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        $this->addSql('CREATE INDEX ShopProductTitleTrgmIdx ON shop_product USING GIN (title gin_trgm_ops)');
        $this->addSql('CREATE INDEX ShopProductSubtitleTrgmIdx ON shop_product USING GIN (subtitle gin_trgm_ops)');
        $this->addSql('CREATE INDEX ShopProductDescriptionTrgmIdx ON shop_product USING GIN (description gin_trgm_ops)');

        $this->addSql('CREATE INDEX ShopCategoryTitleTrgmIdx ON shop_category USING GIN (title gin_trgm_ops)');
        $this->addSql('CREATE INDEX ShopCategoryDescriptionTrgmIdx ON shop_category USING GIN (description gin_trgm_ops)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS ShopCategoryDescriptionTrgmIdx');
        $this->addSql('DROP INDEX IF EXISTS ShopCategoryTitleTrgmIdx');
        $this->addSql('DROP INDEX IF EXISTS ShopCategoryTitleIdx');
        $this->addSql('DROP INDEX IF EXISTS ShopCategoryNbProductIdx');
        $this->addSql('DROP INDEX IF EXISTS ShopCategoryLevelIdx');
        $this->addSql('DROP INDEX IF EXISTS ShopCategoryCreatedAtIdx');

        $this->addSql('DROP INDEX IF EXISTS ShopProductDescriptionTrgmIdx');
        $this->addSql('DROP INDEX IF EXISTS ShopProductSubtitleTrgmIdx');
        $this->addSql('DROP INDEX IF EXISTS ShopProductTitleTrgmIdx');
        $this->addSql('DROP INDEX IF EXISTS ShopProductTitleIdx');
        $this->addSql('DROP INDEX IF EXISTS ShopProductPriceIdx');
        $this->addSql('DROP INDEX IF EXISTS ShopProductCreatedAtIdx');
    }
}
