---
description: Generate, run, and rollback Doctrine migrations including data migrations
model: claude-sonnet-4-6
effort: medium
allowed-tools: [Bash, Read, Edit, Write, Grep, Glob]
argument-hint: "[describe migration, e.g. 'add slug column to products table with unique index']"
---

Generate and manage Doctrine migrations following best practices.

## Migration Specification

$ARGUMENTS

## Existing Project Context

Recent migrations:
!`ls -t migrations/*.php 2>/dev/null | head -5`

Before generating, check recent migrations for naming patterns and SQL conventions.

## What to Generate

Choose the migration approach based on requirements:

### 1. **Generate Migration from Entity Changes** (Recommended)

After modifying entity classes, generate a migration that reflects the diff:

```bash
# Generate migration based on entity changes vs current schema
ddev exec bin/console make:migration

# Alternative: doctrine:migrations:diff (same result)
ddev exec bin/console doctrine:migrations:diff
```

This compares your entity mappings against the current database schema and generates the necessary SQL.

### 2. **Create Blank Migration** (Manual SQL)

For custom SQL, data migrations, or operations not captured by entity diffs:

```bash
# Generate an empty migration class
ddev exec bin/console doctrine:migrations:generate
```

### 3. **Run Migrations**

```bash
# Run all pending migrations
ddev exec bin/console doctrine:migrations:migrate

# Run without confirmation prompt
ddev exec bin/console doctrine:migrations:migrate --no-interaction

# Migrate to a specific version
ddev exec bin/console doctrine:migrations:migrate 'DoctrineMigrations\Version20260410120000'

# Dry run (show SQL without executing)
ddev exec bin/console doctrine:migrations:migrate --dry-run
```

### 4. **Check Migration Status**

```bash
# Show migration status and which are pending
ddev exec bin/console doctrine:migrations:status

# List all migrations and their execution state
ddev exec bin/console doctrine:migrations:list

# Show current database version
ddev exec bin/console doctrine:migrations:current
```

### 5. **Rollback Migrations**

```bash
# Rollback last migration (execute down method)
ddev exec bin/console doctrine:migrations:migrate prev

# Rollback to a specific version
ddev exec bin/console doctrine:migrations:migrate 'DoctrineMigrations\Version20260410110000'

# Execute a single migration down
ddev exec bin/console doctrine:migrations:execute 'DoctrineMigrations\Version20260410120000' --down

# Mark a migration as not executed (without running SQL)
ddev exec bin/console doctrine:migrations:version 'DoctrineMigrations\Version20260410120000' --delete
```

## Migration File Structure

### **Standard Migration** (`migrations/Version20260410120000.php`)

```php
<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260410120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add slug column to products table with unique index';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE products ADD slug VARCHAR(255) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B3BA5A5A989D9B62 ON products (slug)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_B3BA5A5A989D9B62 ON products');
        $this->addSql('ALTER TABLE products DROP slug');
    }
}
```

## Common Migration Patterns

### **Adding Columns**

```php
public function up(Schema $schema): void
{
    // Add a nullable column (safe for existing data)
    $this->addSql('ALTER TABLE users ADD phone VARCHAR(20) DEFAULT NULL');

    // Add a NOT NULL column with default value
    $this->addSql('ALTER TABLE users ADD is_active TINYINT(1) NOT NULL DEFAULT 1');

    // Add a JSON column
    $this->addSql('ALTER TABLE users ADD preferences JSON NOT NULL DEFAULT \'{}\'');

    // Add a datetime column
    $this->addSql('ALTER TABLE users ADD last_login_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
}

public function down(Schema $schema): void
{
    $this->addSql('ALTER TABLE users DROP phone');
    $this->addSql('ALTER TABLE users DROP is_active');
    $this->addSql('ALTER TABLE users DROP preferences');
    $this->addSql('ALTER TABLE users DROP last_login_at');
}
```

### **Creating Tables**

```php
public function up(Schema $schema): void
{
    $this->addSql('CREATE TABLE categories (
        id INT AUTO_INCREMENT NOT NULL,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL,
        description LONGTEXT DEFAULT NULL,
        parent_id INT DEFAULT NULL,
        position INT NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
        updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
        UNIQUE INDEX UNIQ_3AF34668989D9B62 (slug),
        INDEX IDX_3AF34668727ACA70 (parent_id),
        PRIMARY KEY(id)
    ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

    $this->addSql('ALTER TABLE categories ADD CONSTRAINT FK_3AF34668727ACA70
        FOREIGN KEY (parent_id) REFERENCES categories (id) ON DELETE SET NULL');
}

public function down(Schema $schema): void
{
    $this->addSql('ALTER TABLE categories DROP FOREIGN KEY FK_3AF34668727ACA70');
    $this->addSql('DROP TABLE categories');
}
```

### **Adding Indexes**

```php
public function up(Schema $schema): void
{
    // Simple index
    $this->addSql('CREATE INDEX IDX_EMAIL ON users (email)');

    // Unique index
    $this->addSql('CREATE UNIQUE INDEX UNIQ_EMAIL ON users (email)');

    // Composite index
    $this->addSql('CREATE INDEX IDX_STATUS_DATE ON orders (status, created_at)');

    // Fulltext index (MySQL)
    $this->addSql('CREATE FULLTEXT INDEX IDX_SEARCH ON products (name, description)');

    // Partial index (PostgreSQL)
    // $this->addSql('CREATE INDEX IDX_ACTIVE ON users (email) WHERE is_active = true');
}

public function down(Schema $schema): void
{
    $this->addSql('DROP INDEX IDX_EMAIL ON users');
    $this->addSql('DROP INDEX UNIQ_EMAIL ON users');
    $this->addSql('DROP INDEX IDX_STATUS_DATE ON orders');
    $this->addSql('DROP INDEX IDX_SEARCH ON products');
}
```

### **Adding Foreign Keys**

```php
public function up(Schema $schema): void
{
    // Add foreign key column
    $this->addSql('ALTER TABLE posts ADD author_id INT NOT NULL');

    // Add index on FK column (required before adding constraint)
    $this->addSql('CREATE INDEX IDX_AUTHOR ON posts (author_id)');

    // Add foreign key constraint
    $this->addSql('ALTER TABLE posts ADD CONSTRAINT FK_AUTHOR
        FOREIGN KEY (author_id) REFERENCES users (id) ON DELETE CASCADE');
}

public function down(Schema $schema): void
{
    $this->addSql('ALTER TABLE posts DROP FOREIGN KEY FK_AUTHOR');
    $this->addSql('DROP INDEX IDX_AUTHOR ON posts');
    $this->addSql('ALTER TABLE posts DROP author_id');
}
```

### **Many-to-Many Join Table**

```php
public function up(Schema $schema): void
{
    $this->addSql('CREATE TABLE post_tags (
        post_id INT NOT NULL,
        tag_id INT NOT NULL,
        INDEX IDX_POST (post_id),
        INDEX IDX_TAG (tag_id),
        PRIMARY KEY(post_id, tag_id)
    ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

    $this->addSql('ALTER TABLE post_tags ADD CONSTRAINT FK_POST
        FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE');
    $this->addSql('ALTER TABLE post_tags ADD CONSTRAINT FK_TAG
        FOREIGN KEY (tag_id) REFERENCES tags (id) ON DELETE CASCADE');
}

public function down(Schema $schema): void
{
    $this->addSql('ALTER TABLE post_tags DROP FOREIGN KEY FK_POST');
    $this->addSql('ALTER TABLE post_tags DROP FOREIGN KEY FK_TAG');
    $this->addSql('DROP TABLE post_tags');
}
```

### **Renaming Columns**

```php
public function up(Schema $schema): void
{
    $this->addSql('ALTER TABLE users CHANGE username display_name VARCHAR(255) NOT NULL');
}

public function down(Schema $schema): void
{
    $this->addSql('ALTER TABLE users CHANGE display_name username VARCHAR(255) NOT NULL');
}
```

### **Changing Column Type**

```php
public function up(Schema $schema): void
{
    // Change column type (e.g., VARCHAR to TEXT)
    $this->addSql('ALTER TABLE products MODIFY description LONGTEXT DEFAULT NULL');

    // Change column length
    $this->addSql('ALTER TABLE users MODIFY name VARCHAR(500) NOT NULL');
}

public function down(Schema $schema): void
{
    $this->addSql('ALTER TABLE products MODIFY description VARCHAR(255) DEFAULT NULL');
    $this->addSql('ALTER TABLE users MODIFY name VARCHAR(255) NOT NULL');
}
```

## Data Migrations

### **Populate New Column from Existing Data**

```php
public function up(Schema $schema): void
{
    // Step 1: Add column as nullable
    $this->addSql('ALTER TABLE products ADD slug VARCHAR(255) DEFAULT NULL');

    // Step 2: Populate from existing data
    $this->addSql("UPDATE products SET slug = LOWER(REPLACE(REPLACE(name, ' ', '-'), '.', ''))");

    // Step 3: Make column NOT NULL after data is populated
    $this->addSql('ALTER TABLE products MODIFY slug VARCHAR(255) NOT NULL');

    // Step 4: Add unique index
    $this->addSql('CREATE UNIQUE INDEX UNIQ_SLUG ON products (slug)');
}

public function down(Schema $schema): void
{
    $this->addSql('DROP INDEX UNIQ_SLUG ON products');
    $this->addSql('ALTER TABLE products DROP slug');
}
```

### **Splitting a Column**

```php
public function up(Schema $schema): void
{
    // Add new columns
    $this->addSql('ALTER TABLE users ADD first_name VARCHAR(255) DEFAULT NULL');
    $this->addSql('ALTER TABLE users ADD last_name VARCHAR(255) DEFAULT NULL');

    // Migrate data from full_name to first_name + last_name
    $this->addSql("UPDATE users SET
        first_name = SUBSTRING_INDEX(full_name, ' ', 1),
        last_name = SUBSTRING_INDEX(full_name, ' ', -1)
    ");

    // Make columns NOT NULL
    $this->addSql('ALTER TABLE users MODIFY first_name VARCHAR(255) NOT NULL');
    $this->addSql('ALTER TABLE users MODIFY last_name VARCHAR(255) NOT NULL');

    // Drop old column
    $this->addSql('ALTER TABLE users DROP full_name');
}

public function down(Schema $schema): void
{
    $this->addSql('ALTER TABLE users ADD full_name VARCHAR(255) DEFAULT NULL');
    $this->addSql("UPDATE users SET full_name = CONCAT(first_name, ' ', last_name)");
    $this->addSql('ALTER TABLE users MODIFY full_name VARCHAR(255) NOT NULL');
    $this->addSql('ALTER TABLE users DROP first_name');
    $this->addSql('ALTER TABLE users DROP last_name');
}
```

### **Enum/Status Migration**

```php
public function up(Schema $schema): void
{
    // Add new status column
    $this->addSql("ALTER TABLE orders ADD status VARCHAR(20) NOT NULL DEFAULT 'pending'");

    // Migrate from boolean to enum-style
    $this->addSql("UPDATE orders SET status = 'completed' WHERE is_completed = 1");
    $this->addSql("UPDATE orders SET status = 'cancelled' WHERE is_cancelled = 1");

    // Drop old boolean columns
    $this->addSql('ALTER TABLE orders DROP is_completed');
    $this->addSql('ALTER TABLE orders DROP is_cancelled');

    // Add index on status
    $this->addSql('CREATE INDEX IDX_STATUS ON orders (status)');
}

public function down(Schema $schema): void
{
    $this->addSql('ALTER TABLE orders ADD is_completed TINYINT(1) NOT NULL DEFAULT 0');
    $this->addSql('ALTER TABLE orders ADD is_cancelled TINYINT(1) NOT NULL DEFAULT 0');
    $this->addSql("UPDATE orders SET is_completed = 1 WHERE status = 'completed'");
    $this->addSql("UPDATE orders SET is_cancelled = 1 WHERE status = 'cancelled'");
    $this->addSql('DROP INDEX IDX_STATUS ON orders');
    $this->addSql('ALTER TABLE orders DROP status');
}
```

## Using the Schema API

For database-agnostic migrations, use the Schema object instead of raw SQL:

```php
public function up(Schema $schema): void
{
    $table = $schema->createTable('tags');
    $table->addColumn('id', 'integer', ['autoincrement' => true]);
    $table->addColumn('name', 'string', ['length' => 100]);
    $table->addColumn('slug', 'string', ['length' => 100]);
    $table->addColumn('created_at', 'datetime_immutable');
    $table->setPrimaryKey(['id']);
    $table->addUniqueIndex(['slug'], 'UNIQ_TAG_SLUG');
    $table->addIndex(['name'], 'IDX_TAG_NAME');
}

public function down(Schema $schema): void
{
    $schema->dropTable('tags');
}
```

### **Modifying Existing Tables with Schema API**

```php
public function up(Schema $schema): void
{
    $table = $schema->getTable('products');
    $table->addColumn('sku', 'string', ['length' => 50, 'notnull' => false]);
    $table->addColumn('weight', 'decimal', ['precision' => 8, 'scale' => 2, 'notnull' => false]);
    $table->addUniqueIndex(['sku'], 'UNIQ_PRODUCT_SKU');
}

public function down(Schema $schema): void
{
    $table = $schema->getTable('products');
    $table->dropIndex('UNIQ_PRODUCT_SKU');
    $table->dropColumn('sku');
    $table->dropColumn('weight');
}
```

## Migration Configuration

### **doctrine_migrations.yaml** (`config/packages/doctrine_migrations.yaml`)

```yaml
doctrine_migrations:
    migrations_paths:
        'DoctrineMigrations': '%kernel.project_dir%/migrations'
    enable_profiler: false
    # Organize migrations across multiple directories
    # 'App\Migrations\Core': '%kernel.project_dir%/migrations/core'
    # 'App\Migrations\Tenant': '%kernel.project_dir%/migrations/tenant'
```

## DDEV Commands Reference

```bash
# Generate migration from entity changes
ddev exec bin/console make:migration

# Generate empty migration
ddev exec bin/console doctrine:migrations:generate

# Run all pending migrations
ddev exec bin/console doctrine:migrations:migrate --no-interaction

# Dry run (preview SQL)
ddev exec bin/console doctrine:migrations:migrate --dry-run

# Check status
ddev exec bin/console doctrine:migrations:status

# List all migrations
ddev exec bin/console doctrine:migrations:list

# Rollback to previous version
ddev exec bin/console doctrine:migrations:migrate prev

# Execute specific migration down
ddev exec bin/console doctrine:migrations:execute 'DoctrineMigrations\Version20260410120000' --down

# Mark migration as executed without running it
ddev exec bin/console doctrine:migrations:version 'DoctrineMigrations\Version20260410120000' --add

# Mark migration as not executed without running down
ddev exec bin/console doctrine:migrations:version 'DoctrineMigrations\Version20260410120000' --delete

# Validate schema against entities
ddev exec bin/console doctrine:schema:validate

# Show pending schema diff SQL
ddev exec bin/console doctrine:schema:update --dump-sql

# Reset database completely
ddev exec bin/console doctrine:database:drop --force && ddev exec bin/console doctrine:database:create && ddev exec bin/console doctrine:migrations:migrate --no-interaction
```

## Best Practices

**Migration Safety**
- Always review generated migrations before running them
- Never edit a migration that has already been executed
- Always provide a working `down()` method for rollback
- Test migrations on a copy of production data before deploying
- Keep migrations in version control

**Data Integrity**
- Add new NOT NULL columns as nullable first, populate data, then add the constraint
- Use transactions for data migrations when possible
- Back up data before running destructive migrations
- Add indexes on foreign key columns

**Naming and Organization**
- Use `getDescription()` to document what the migration does
- One logical change per migration (do not combine unrelated changes)
- Keep migrations small and focused
- Version timestamps ensure correct execution order

**Performance**
- Add indexes for columns used in WHERE, JOIN, and ORDER BY clauses
- Avoid full table scans in data migrations on large tables
- Consider running data migrations in batches for large datasets
- Use `ALTER TABLE ... ALGORITHM=INPLACE` for MySQL when possible

**Team Workflow**
- Run `doctrine:migrations:status` before starting new work
- Resolve migration conflicts by regenerating, not merging
- Coordinate migrations that affect shared tables
- Use `--dry-run` to preview changes before applying

Generate and manage Doctrine migrations that safely evolve your database schema in DDEV environments.

## Next Steps

After running migrations, consider:
- `/fixture-new` — Create fixtures for new tables
- `/entity-new` — Continue evolving your entity model
