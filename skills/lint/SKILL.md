---
description: Run PHP CS Fixer, PHPStan, and Rector to fix code quality issues
model: claude-sonnet-4-6
effort: low
allowed-tools: [Bash, Read, Edit, Write, Grep, Glob]
paths: ["src/**/*.php", "tests/**/*.php"]
---

Run linting and fix code quality issues in the codebase.

This skill bundles starter config files in `${CLAUDE_SKILL_DIR}/templates/`. If the project is missing lint config, copy them:
- `${CLAUDE_SKILL_DIR}/templates/php-cs-fixer.dist.php` → `.php-cs-fixer.dist.php`
- `${CLAUDE_SKILL_DIR}/templates/phpstan.neon` → `phpstan.neon`
- `${CLAUDE_SKILL_DIR}/templates/rector.php` → `rector.php`

## Target

$ARGUMENTS

## Lint Strategy

### 1. **Run Linting Commands**

```bash
# PHP CS Fixer (code style)
ddev exec vendor/bin/php-cs-fixer fix --dry-run --diff
ddev exec vendor/bin/php-cs-fixer fix

# PHPStan (static analysis)
ddev exec vendor/bin/phpstan analyse src tests

# Rector (automated refactoring)
ddev exec vendor/bin/rector process --dry-run
ddev exec vendor/bin/rector process

# Symfony linter (config/templates)
ddev exec bin/console lint:twig templates/
ddev exec bin/console lint:yaml config/
ddev exec bin/console lint:container

# All together
ddev exec vendor/bin/php-cs-fixer fix && ddev exec vendor/bin/phpstan analyse src tests
```

### 2. **Common PHP Issues**

**Type Safety**
- Missing `declare(strict_types=1)`
- Use of `mixed` type without justification
- Missing return type declarations
- Missing parameter type hints
- `@var` annotations instead of typed properties

**Symfony Patterns**
- Deprecated Symfony API calls
- Missing `#[Route]` attributes (using YAML/XML routes instead)
- Manual service instantiation instead of autowiring
- Missing `readonly` on immutable services
- Non-final controller classes

**Code Quality**
- Unused imports (`use` statements)
- Unused private methods/properties
- Dead code after return/throw
- Empty catch blocks
- Overly complex methods (cyclomatic complexity > 10)

**Security**
- Raw SQL without parameter binding
- Missing CSRF token validation
- Unescaped output in Twig (`|raw` without justification)
- Sensitive data in logs

### 3. **Auto-Fix What You Can**

**Safe Auto-Fixes**
```bash
# Fix code style (PSR-12 + Symfony standards)
ddev exec vendor/bin/php-cs-fixer fix

# Fix with Rector (PHP upgrades, pattern improvements)
ddev exec vendor/bin/rector process

# Fix import ordering
ddev exec vendor/bin/php-cs-fixer fix --rules='ordered_imports'
```

**Manual Fixes Needed**
- Type annotations requiring logic understanding
- Architecture violations (fat controllers, service coupling)
- Missing validation constraints
- Security issues

### 4. **Configuration**

**PHP CS Fixer** (`.php-cs-fixer.dist.php`)
```php
<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('var')
    ->exclude('vendor')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        '@PhpCsFixer' => true,
        'declare_strict_types' => true,
        'void_return' => true,
        'native_function_invocation' => ['include' => ['@compiler_optimized'], 'scope' => 'namespaced'],
        'global_namespace_import' => ['import_classes' => true, 'import_functions' => false, 'import_constants' => false],
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(true)
;
```

**PHPStan** (`phpstan.neon`)
```neon
parameters:
    level: 8
    paths:
        - src
        - tests
    excludePaths:
        - src/Kernel.php
    symfony:
        containerXmlPath: var/cache/dev/App_KernelDevDebugContainer.xml
    treatPhpDocTypesAsCertain: false
```

**Rector** (`rector.php`)
```php
<?php

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;
use Rector\Symfony\Set\SymfonySetList;

return RectorConfig::configure()
    ->withPaths([__DIR__ . '/src', __DIR__ . '/tests'])
    ->withSets([
        SetList::PHP_82,
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::TYPE_DECLARATION,
        SymfonySetList::SYMFONY_70,
        SymfonySetList::SYMFONY_CODE_QUALITY,
    ])
;
```

### 5. **Priority Fixes**

**High Priority** (fix immediately)
- PHPStan level 8 errors (type safety)
- Security vulnerabilities (SQL injection, XSS)
- Deprecated Symfony API usage
- Missing strict_types declarations

**Medium Priority** (fix before commit)
- Code style violations (PHP CS Fixer)
- Unused imports and dead code
- Missing return types
- Overly complex methods

**Low Priority** (fix when convenient)
- Minor formatting inconsistencies
- PHPDoc improvements
- Rector suggestions for modernization

### 6. **Pre-Commit Hooks** (Recommended)

**GrumPHP** (`grumphp.yml`)
```yaml
grumphp:
    tasks:
        phpcsfixer:
            config: .php-cs-fixer.dist.php
        phpstan:
            configuration: phpstan.neon
            level: 8
        phpunit:
            always_execute: true
```

**Install**
```bash
ddev exec composer require --dev phpro/grumphp
```

### 7. **IDE Integration**

**PHPStorm / IntelliJ**
- Enable PHP CS Fixer as external tool or quality tool
- Configure PHPStan inspection
- Enable Symfony plugin for route/service resolution

## What to Generate

1. **Lint Report** - All issues found with severity levels
2. **Auto-Fix Results** - What was automatically fixed by PHP CS Fixer / Rector
3. **Manual Fix Suggestions** - Issues requiring human judgment
4. **Priority List** - Ordered by severity (type errors > security > style)
5. **Configuration Recommendations** - Improve lint setup if config files are missing

## Common Fixes

**Add strict_types declaration**
```php
// Before
<?php

namespace App\Service;

// After
<?php

declare(strict_types=1);

namespace App\Service;
```

**Add return type declarations**
```php
// Before
public function findActiveProducts()
{
    return $this->repository->findBy(['active' => true]);
}

// After
/** @return Product[] */
public function findActiveProducts(): array
{
    return $this->repository->findBy(['active' => true]);
}
```

**Replace mixed with proper types**
```php
// Before
public function process(mixed $data): mixed
{
    return $data['value'];
}

// After
/** @param array{value: string} $data */
public function process(array $data): string
{
    return $data['value'];
}
```

**Fix Doctrine query type safety**
```php
// Before
$qb->where('p.status = ' . $status);

// After
$qb->where('p.status = :status')
   ->setParameter('status', $status);
```

Focus on fixes that improve type safety and prevent runtime errors. Run linting before every commit.
