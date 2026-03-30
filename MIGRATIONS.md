# DTZ Database Migrations

## 🚀 Quick Start

### Installation
```bash
# Install dependencies (includes Phinx)
composer install

# Run migrations
php bin/dtz migrate

# Or use composer
composer migrate

# Seed with sample data
php bin/dtz seed
```

### Fresh Install (with sample data)
```bash
php bin/dtz fresh
```

## 📁 Directory Structure

```
dtz-lid-de/
├── db/
│   ├── migrations/          # Phinx migration files
│   │   ├── 20240330000001_initial_schema.php
│   │   ├── 20240330000002_writing_submissions.php
│   │   ├── 20240330000003_speaking_submissions.php
│   │   └── 20240330000004_ai_features.php
│   └── seeds/               # Database seeders
│       ├── UserSeeder.php
│       └── QuestionSeeder.php
├── bin/
│   └── dtz                  # CLI tool
├── phinx.php                # Phinx configuration
└── MIGRATIONS.md           # This file
```

## 🛠️ Available Commands

### CLI Tool (`php bin/dtz`)

| Command | Description |
|---------|-------------|
| `migrate` | Run all pending migrations |
| `migrate:rollback` | Rollback last migration |
| `migrate:status` | Show migration status |
| `seed` | Run database seeders |
| `fresh` | Drop all tables, migrate and seed |
| `reset` | Rollback all migrations |
| `help` | Show help |

### Composer Scripts

| Command | Description |
|---------|-------------|
| `composer migrate` | Run migrations |
| `composer migrate:rollback` | Rollback one step |
| `composer migrate:status` | Show status |
| `composer seed` | Run seeders |
| `composer db:fresh` | Fresh install |
| `composer db:reset` | Reset all |

## 📝 Creating New Migrations

```bash
# Create a new migration
vendor/bin/phinx create NewFeatureMigration -c phinx.php

# Or with timestamp
vendor/bin/phinx create AddUserPreferences -c phinx.php
```

Migration file will be created in `db/migrations/` with a timestamp prefix.

### Migration Template

```php
<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class NewFeatureMigration extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('new_table');
        $table->addColumn('name', 'string', ['limit' => 255])
              ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
              ->create();
    }
}
```

## 🌱 Creating Seeders

```bash
# Create a new seeder
vendor/bin/phinx seed:create NewSeeder -c phinx.php
```

### Seeder Template

```php
<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class NewSeeder extends AbstractSeed
{
    public function run(): void
    {
        $data = [
            ['name' => 'Sample', 'created_at' => date('Y-m-d H:i:s')],
        ];
        
        $table = $this->table('table_name');
        $table->insert($data)->saveData();
    }
}
```

## 🔄 Migration Workflow

### 1. Development
```bash
# Create migration
vendor/bin/phinx create AddNewTable -c phinx.php

# Edit the migration file
# ...

# Run migration locally
php bin/dtz migrate

# Test rollback
php bin/dtz migrate:rollback
```

### 2. Testing
```bash
# Fresh install for testing
php bin/dtz fresh

# Or specific environment
vendor/bin/phinx migrate -c phinx.php -e testing
```

### 3. Production
```bash
# Check status first
php bin/dtz migrate:status

# Run migrations (CI/CD should do this automatically)
php bin/dtz migrate
```

## 🔧 Configuration

### Database Types

**SQLite (Default):**
```bash
# Uses database/dtz.db
export DB_TYPE=sqlite
```

**PostgreSQL:**
```bash
export DB_TYPE=pgsql
export DB_HOST=localhost
export DB_NAME=dtz_production
export DB_USER=dtz_user
export DB_PASSWORD=secret
export DB_PORT=5432
```

### Environments

Phinx supports 3 environments:
- `development` (default)
- `testing`
- `production`

```bash
# Run on specific environment
vendor/bin/phinx migrate -c phinx.php -e production
```

## ⚠️ Important Notes

1. **Always backup** before running `fresh` or `reset`
2. **Never modify** existing migration files after pushing to production
3. **Test rollbacks** before deploying
4. **Use transactions** in migrations when possible

## 📊 Migration History

| Version | Date | Description |
|---------|------|-------------|
| 20240330000001 | 2024-03-30 | Initial schema (users, questions, answers, etc.) |
| 20240330000002 | 2024-03-30 | Writing submissions table |
| 20240330000003 | 2024-03-30 | Speaking submissions table |
| 20240330000004 | 2024-03-30 | AI features (cache, interactions, mistakes) |

## 🔗 Related Documentation

- [Phinx Documentation](https://book.cakephp.org/phinx/0/en/index.html)
- [SQLite Documentation](https://www.sqlite.org/docs.html)
- [PostgreSQL Documentation](https://www.postgresql.org/docs/)
