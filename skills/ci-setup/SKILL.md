---
description: Generate GitHub Actions or GitLab CI pipeline for Symfony with tests, lint, and deploy
model: claude-sonnet-4-6
effort: medium
argument-hint: "[describe CI target, e.g. 'GitHub Actions with PHPUnit, PHPStan, and deploy to staging']"
---

Generate a CI/CD pipeline configuration for a Symfony application.

## CI Requirements

$ARGUMENTS

## Existing Project Context

CI config:
!`ls .github/workflows/*.yml .gitlab-ci.yml 2>/dev/null`

PHP version:
!`grep '"php"' composer.json 2>/dev/null`

Test config:
!`ls phpunit.xml.dist phpunit.xml phpstan.neon .php-cs-fixer.dist.php 2>/dev/null`

## GitHub Actions Pipeline

### Basic Pipeline (Tests + Lint)

**File:** `.github/workflows/ci.yml`

```yaml
name: CI

on:
  push:
    branches: [main]
  pull_request:
    branches: [main]

jobs:
  tests:
    name: Tests
    runs-on: ubuntu-latest

    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: app_test
        ports:
          - 3306:3306
        options: >-
          --health-cmd="mysqladmin ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=3

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: mbstring, intl, pdo_mysql
          coverage: xdebug

      - name: Cache Composer
        uses: actions/cache@v4
        with:
          path: vendor
          key: composer-${{ hashFiles('composer.lock') }}

      - name: Install dependencies
        run: composer install --prefer-dist --no-progress

      - name: Create database
        env:
          DATABASE_URL: mysql://root:root@127.0.0.1:3306/app_test
        run: |
          bin/console doctrine:database:create --if-not-exists --env=test
          bin/console doctrine:migrations:migrate --no-interaction --env=test

      - name: Run PHPUnit
        env:
          DATABASE_URL: mysql://root:root@127.0.0.1:3306/app_test
        run: bin/phpunit --coverage-text

  lint:
    name: Code Quality
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: mbstring, intl

      - name: Install dependencies
        run: composer install --prefer-dist --no-progress

      - name: PHP CS Fixer
        run: vendor/bin/php-cs-fixer fix --dry-run --diff

      - name: PHPStan
        run: vendor/bin/phpstan analyse

      - name: Symfony Lint
        run: |
          bin/console lint:twig templates/
          bin/console lint:yaml config/
          bin/console lint:container
```

### Pipeline with Deploy

```yaml
  deploy-staging:
    name: Deploy to Staging
    needs: [tests, lint]
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/main'

    steps:
      - uses: actions/checkout@v4

      - name: Deploy
        uses: appleboy/ssh-action@v1
        with:
          host: ${{ secrets.STAGING_HOST }}
          username: ${{ secrets.STAGING_USER }}
          key: ${{ secrets.STAGING_SSH_KEY }}
          script: |
            cd /var/www/staging
            git pull origin main
            composer install --no-dev --optimize-autoloader
            bin/console doctrine:migrations:migrate --no-interaction
            bin/console cache:clear --env=prod
            bin/console asset-map:compile
            supervisorctl restart messenger-consume:*
```

## GitLab CI Pipeline

**File:** `.gitlab-ci.yml`

```yaml
stages:
  - test
  - lint
  - deploy

variables:
  MYSQL_ROOT_PASSWORD: root
  MYSQL_DATABASE: app_test
  DATABASE_URL: mysql://root:root@mysql:3306/app_test

tests:
  stage: test
  image: php:8.2-cli
  services:
    - mysql:8.0
  before_script:
    - apt-get update && apt-get install -y git unzip libicu-dev
    - docker-php-ext-install intl pdo_mysql
    - curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
    - composer install --prefer-dist --no-progress
    - bin/console doctrine:database:create --if-not-exists --env=test
    - bin/console doctrine:migrations:migrate --no-interaction --env=test
  script:
    - bin/phpunit --coverage-text
  cache:
    key: composer
    paths:
      - vendor/

lint:
  stage: lint
  image: php:8.2-cli
  before_script:
    - apt-get update && apt-get install -y git unzip libicu-dev
    - docker-php-ext-install intl
    - curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
    - composer install --prefer-dist --no-progress
  script:
    - vendor/bin/php-cs-fixer fix --dry-run --diff
    - vendor/bin/phpstan analyse
  cache:
    key: composer
    paths:
      - vendor/
```

## Best Practices

- Run tests and lint in parallel jobs (faster feedback)
- Cache `vendor/` by `composer.lock` hash
- Use the same PHP version as production
- Run migrations in test pipeline against a real database
- Keep deploy jobs behind branch protection and manual approval for production

Generate a CI/CD pipeline configuration tailored to the project's needs.

## Next Steps

After setting up CI, consider:
- `/deploy` — Create a production deployment checklist
- `/test-new` — Ensure test coverage before enabling CI
