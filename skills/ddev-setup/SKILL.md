---
description: Configure DDEV for a Symfony project with database, PHP version, and services
model: claude-sonnet-4-6
effort: medium
argument-hint: "[describe setup, e.g. 'PHP 8.3 with MySQL 8, Redis, and Mailpit']"
---

Configure DDEV for a Symfony project.

## Setup Requirements

$ARGUMENTS

## Existing Project Context

Current DDEV config:
!`cat .ddev/config.yaml 2>/dev/null || echo "No DDEV config found"`

PHP version in composer.json:
!`grep '"php"' composer.json 2>/dev/null`

## DDEV Configuration

### Basic Setup

```bash
# Initialize DDEV in a Symfony project
ddev config --project-type=symfony --php-version=8.2 --docroot=public
ddev start
```

### Configuration File

**File:** `.ddev/config.yaml`

```yaml
name: my-project
type: symfony
docroot: public
php_version: "8.3"
webserver_type: nginx-fpm
database:
  type: mysql
  version: "8.0"
router_http_port: "80"
router_https_port: "443"

# PHP settings
php_version: "8.3"
xdebug_enabled: false

# Hooks
hooks:
  post-start:
    - exec: composer install
    - exec: bin/console doctrine:migrations:migrate --no-interaction
    - exec: bin/console cache:clear
```

### Database Options

**MySQL 8:**
```yaml
database:
  type: mysql
  version: "8.0"
```

**PostgreSQL 16:**
```yaml
database:
  type: postgres
  version: "16"
```

**MariaDB 11:**
```yaml
database:
  type: mariadb
  version: "11"
```

### Add Redis

```bash
ddev get ddev/ddev-redis
```

After installing, configure Symfony to use Redis:

```yaml
# config/packages/cache.yaml
framework:
    cache:
        app: cache.adapter.redis
        default_redis_provider: 'redis://redis:6379'
```

```yaml
# config/packages/messenger.yaml (for async transport)
framework:
    messenger:
        transports:
            async:
                dsn: 'redis://redis:6379/messages'
```

### Add Mailpit (Email Testing)

```bash
ddev get ddev/ddev-mailpit
```

Configure Symfony mailer:
```env
# .env.local
MAILER_DSN=smtp://localhost:1025
```

Access Mailpit UI at `https://my-project.ddev.site:8026`

### Add Elasticsearch

```bash
ddev get ddev/ddev-elasticsearch
```

### Xdebug

```bash
# Enable Xdebug for debugging
ddev xdebug on

# Disable when not needed (improves performance)
ddev xdebug off
```

PHPStorm configuration:
1. Set CLI Interpreter to DDEV
2. Set path mappings: `/var/www/html` → project root
3. Start listening for PHP Debug Connections

### Custom PHP Configuration

**File:** `.ddev/php/custom.ini`

```ini
memory_limit = 512M
upload_max_filesize = 64M
post_max_size = 64M
max_execution_time = 300
opcache.memory_consumption = 256
```

### Custom Nginx Configuration

**File:** `.ddev/nginx_full/symfony.conf`

```nginx
server {
    listen 80 default_server;
    listen 443 ssl default_server;

    root /var/www/html/public;

    ssl_certificate /etc/ssl/certs/master.crt;
    ssl_certificate_key /etc/ssl/certs/master.key;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/run/php-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        internal;
    }
}
```

## Symfony + DDEV Workflow

```bash
# Start project
ddev start

# Run Symfony console commands
ddev exec bin/console cache:clear
ddev exec bin/console debug:router
ddev exec bin/console debug:container --tag=controller.service_arguments

# Database
ddev exec bin/console doctrine:database:create
ddev exec bin/console make:migration
ddev exec bin/console doctrine:migrations:migrate

# Tests
ddev exec bin/phpunit
ddev exec bin/phpunit --filter=ProductServiceTest
ddev exec bin/phpunit --coverage-html var/coverage

# Code quality
ddev exec vendor/bin/php-cs-fixer fix
ddev exec vendor/bin/phpstan analyse
ddev exec vendor/bin/rector process

# Fixtures
ddev exec bin/console doctrine:fixtures:load --no-interaction

# Composer
ddev composer require symfony/messenger
ddev composer require --dev phpstan/phpstan

# Database access
ddev mysql
ddev describe  # shows connection details

# Logs
ddev logs -f
```

## Environment Variables

**File:** `.env.local` (for DDEV)

```env
APP_ENV=dev
APP_SECRET=your-dev-secret-here
DATABASE_URL="mysql://db:db@db:3306/db?serverVersion=8.0"
MAILER_DSN=smtp://localhost:1025
MESSENGER_TRANSPORT_DSN=doctrine://default
```

Generate a complete DDEV configuration for the Symfony project.

## Next Steps

After setting up DDEV, consider:
- `/entity-new` — Create your first Doctrine entity
- `/ci-setup` — Configure CI pipeline that mirrors your DDEV setup
