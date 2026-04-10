---
description: Generate deployment checklist and production config for Symfony applications
model: claude-sonnet-4-6
effort: medium
argument-hint: "[describe target, e.g. 'deploy to DigitalOcean with GitHub Actions']"
---

Generate a deployment checklist and production configuration for a Symfony application.

## Deployment Target

$ARGUMENTS

## Existing Project Context

Current environment config:
!`ls .env* 2>/dev/null`
!`grep -E "^APP_ENV|^APP_SECRET|^DATABASE_URL|^MAILER_DSN|^MESSENGER_TRANSPORT" .env 2>/dev/null`

Composer dependencies:
!`head -5 composer.json 2>/dev/null`

## Pre-Deployment Checklist

Before deploying, verify:

### 1. Environment Configuration

```bash
# Production .env.local (never commit this)
APP_ENV=prod
APP_SECRET=<generate-with-openssl>
APP_DEBUG=0
DATABASE_URL="mysql://user:pass@host:3306/dbname?serverVersion=8.0"
MAILER_DSN=smtp://user:pass@smtp.example.com:587
MESSENGER_TRANSPORT_DSN=doctrine://default
```

```bash
# Generate a secure APP_SECRET
openssl rand -hex 16
```

### 2. Production Optimizations

```bash
# Install without dev dependencies
composer install --no-dev --optimize-autoloader

# Clear and warm cache for production
bin/console cache:clear --env=prod
bin/console cache:warmup --env=prod

# Compile assets
bin/console asset-map:compile

# Run migrations
bin/console doctrine:migrations:migrate --no-interaction

# Verify schema matches entities
bin/console doctrine:schema:validate
```

### 3. PHP Configuration

```ini
; php.ini production settings
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
realpath_cache_size=4096K
realpath_cache_ttl=600
```

### 4. Web Server Configuration

**Nginx:**
```nginx
server {
    listen 80;
    server_name example.com;
    root /var/www/project/public;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/run/php/php-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        internal;
    }

    location ~ \.php$ {
        return 404;
    }
}
```

### 5. Messenger Workers

```ini
; /etc/supervisor/conf.d/messenger-worker.conf
[program:messenger-consume]
command=php /var/www/project/bin/console messenger:consume async --time-limit=3600 --memory-limit=128M
user=www-data
numprocs=2
autostart=true
autorestart=true
process_name=%(program_name)s_%(process_num)02d
```

### 6. Security Checklist

- [ ] `APP_DEBUG=0` in production
- [ ] `APP_ENV=prod`
- [ ] Unique `APP_SECRET` per environment
- [ ] Database credentials not in `.env` (use `.env.local` or vault)
- [ ] HTTPS enforced (redirect HTTP → HTTPS)
- [ ] Debug toolbar disabled (`web_profiler` not loaded in prod)
- [ ] Error pages configured (custom 404, 500)
- [ ] Rate limiting on login and registration endpoints
- [ ] CORS configured for API endpoints
- [ ] CSP headers set

### 7. Monitoring

```yaml
# config/packages/prod/monolog.yaml
monolog:
    handlers:
        main:
            type: fingers_crossed
            action_level: error
            handler: nested
            excluded_http_codes: [404, 405]
        nested:
            type: stream
            path: "%kernel.logs_dir%/%kernel.environment%.log"
            level: debug
```

### 8. Deploy Script Template

```bash
#!/bin/bash
set -e

echo "Pulling latest code..."
git pull origin main

echo "Installing dependencies..."
composer install --no-dev --optimize-autoloader

echo "Running migrations..."
bin/console doctrine:migrations:migrate --no-interaction

echo "Clearing cache..."
bin/console cache:clear --env=prod
bin/console cache:warmup --env=prod

echo "Compiling assets..."
bin/console asset-map:compile

echo "Restarting workers..."
supervisorctl restart messenger-consume:*

echo "Deploy complete."
```

## Platform-Specific Guides

Adapt the checklist above for your target platform. Include platform-specific deployment commands, CI integration, and environment variable management.

Generate a complete, actionable deployment plan for the specified target.
