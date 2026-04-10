---
description: Create a Doctrine entity with validation and migrations
model: claude-sonnet-4-6
effort: high
paths: ["src/Entity/**/*.php"]
argument-hint: "[describe entity, e.g. 'Product with name, price, category, and timestamps']"
---

Create a Doctrine entity following best practices.

## Entity Specification

$ARGUMENTS

## What to Generate

Generate Doctrine entities from database schema or create/update database schema from entities.

## Workflow Options

### **Option 1: Database-First** (Generate Entities from Existing DB)

Generate Doctrine entities from an existing database:

```bash
# Inside DDEV container
ddev exec bin/console doctrine:mapping:import "App\Entity" attribute --path=src/Entity

# Or outside DDEV (if configured)
bin/console doctrine:mapping:import "App\Entity" attribute --path=src/Entity
```

This creates entity classes with PHP 8 attributes based on your database tables.

### **Option 2: Code-First** (Generate DB from Entities - Recommended)

1. **Create Entity Manually or with Maker**

```bash
# Create new entity interactively
ddev exec bin/console make:entity User

# This creates src/Entity/User.php and src/Repository/UserRepository.php
```

2. **Generate Migration**

```bash
# Generate migration based on entity changes
ddev exec bin/console make:migration

# Review the generated migration in migrations/
```

3. **Run Migration**

```bash
# Apply migration to database
ddev exec bin/console doctrine:migrations:migrate

# Or migrate with confirmation
ddev exec bin/console doctrine:migrations:migrate --no-interaction
```

## Doctrine Entity Structure

### **Example Entity** (`src/Entity/User.php`)

```php
<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\Index(name: 'idx_email', columns: ['email'])]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private string $email;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 255)]
    private string $name;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // Getters and setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}
```

## Common Entity Patterns

### **Relationships**

**One-to-Many**
```php
// User.php (one side)
#[ORM\OneToMany(targetEntity: Post::class, mappedBy: 'author')]
private Collection $posts;

public function __construct()
{
    $this->posts = new ArrayCollection();
}

// Post.php (many side)
#[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'posts')]
#[ORM\JoinColumn(nullable: false)]
private User $author;
```

**Many-to-Many**
```php
#[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'posts')]
#[ORM\JoinTable(name: 'post_tags')]
private Collection $tags;
```

**One-to-One**
```php
#[ORM\OneToOne(targetEntity: Profile::class, cascade: ['persist', 'remove'])]
private ?Profile $profile = null;
```

### **Enums** (PHP 8.1+)

```php
enum UserStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case BANNED = 'banned';
}

// In entity
#[ORM\Column(type: 'string', enumType: UserStatus::class)]
private UserStatus $status = UserStatus::ACTIVE;
```

### **Timestamps (Lifecycle Callbacks)**

```php
#[ORM\HasLifecycleCallbacks]
class User
{
    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
```

## Useful Doctrine Commands (DDEV)

### **Schema Management**
```bash
# Validate schema
ddev exec bin/console doctrine:schema:validate

# Show SQL for schema changes (without executing)
ddev exec bin/console doctrine:schema:update --dump-sql

# Update schema (dangerous - use migrations instead)
ddev exec bin/console doctrine:schema:update --force
```

### **Migration Management**
```bash
# Generate migration
ddev exec bin/console make:migration

# Show migration status
ddev exec bin/console doctrine:migrations:status

# Execute specific migration
ddev exec bin/console doctrine:migrations:execute --up VERSION

# Rollback migration
ddev exec bin/console doctrine:migrations:execute --down VERSION

# Sync migration version without executing
ddev exec bin/console doctrine:migrations:version --add VERSION
```

### **Database Utilities**
```bash
# Create database
ddev exec bin/console doctrine:database:create

# Drop database
ddev exec bin/console doctrine:database:drop --force

# Show SQL for entity
ddev exec bin/console doctrine:mapping:info

# Show DQL query
ddev exec bin/console doctrine:query:dql "SELECT u FROM App\Entity\User u"

# Run SQL query
ddev exec bin/console doctrine:query:sql "SELECT * FROM users LIMIT 10"
```

## Type Mapping Reference

| PHP Type | Doctrine Type | Database Type |
|----------|--------------|---------------|
| `int` | `Types::INTEGER` | INT |
| `string` | `Types::STRING` | VARCHAR |
| `string` | `Types::TEXT` | TEXT |
| `bool` | `Types::BOOLEAN` | TINYINT(1) |
| `float` | `Types::FLOAT` | DOUBLE |
| `array` | `Types::JSON` | JSON |
| `\DateTime` | `Types::DATETIME_MUTABLE` | DATETIME |
| `\DateTimeImmutable` | `Types::DATETIME_IMMUTABLE` | DATETIME |
| `enum` | `Types::STRING` + enumType | VARCHAR |

## Best Practices

### **Entity Design**
- Use strict PHP types (`declare(strict_types=1)`)
- Prefer immutable value objects
- Use DateTimeImmutable for timestamps
- Type-hint everything (no mixed types)
- Use readonly properties where appropriate (PHP 8.1+)
- Keep entities focused (Single Responsibility)

### **Validation**
- Use Symfony Validator constraints on properties
- Separate validation from persistence
- Create custom constraints for complex rules
- Validate at the entity level, not controller

### **Performance**
- Use indexes on frequently queried columns
- Eager load relationships to avoid N+1 queries
- Use partial objects for large datasets
- Implement pagination with Doctrine Paginator
- Use DQL for complex queries instead of loading all entities

### **Migrations**
- NEVER edit executed migrations
- Always review generated migrations
- Test migrations on copy of production data
- Keep migrations in version control
- Document breaking changes

### **Repository Pattern**
```php
// src/Repository/UserRepository.php
class UserRepository extends ServiceEntityRepository
{
    public function findActiveUsers(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.status = :status')
            ->setParameter('status', UserStatus::ACTIVE)
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByEmailWithPosts(string $email): ?User
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.posts', 'p')
            ->addSelect('p')
            ->where('u.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
```

## Workflow Summary

### **Adding New Entity**
1. `ddev exec bin/console make:entity EntityName`
2. Add properties interactively or edit entity manually
3. `ddev exec bin/console make:migration`
4. Review migration file
5. `ddev exec bin/console doctrine:migrations:migrate`

### **Updating Existing Entity**
1. `ddev exec bin/console make:entity EntityName` (to add properties)
2. Or manually edit entity class
3. `ddev exec bin/console make:migration`
4. Review migration file
5. `ddev exec bin/console doctrine:migrations:migrate`

### **Importing from Database**
1. `ddev exec bin/console doctrine:mapping:import "App\Entity" attribute --path=src/Entity`
2. Clean up generated entities (add types, validation, etc.)
3. Generate repositories if needed

## Testing Entities

```php
// tests/Entity/UserTest.php
use PHPUnit\Framework\TestCase;
use App\Entity\User;

class UserTest extends TestCase
{
    public function testUserCreation(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setName('Test User');

        $this->assertEquals('test@example.com', $user->getEmail());
        $this->assertEquals('Test User', $user->getName());
        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getCreatedAt());
    }
}
```

## DDEV Integration Tips

- Database is available at `db` hostname inside container
- Use `ddev describe` to see database credentials
- Access database: `ddev mysql` or use GUI tools
- Database persists across `ddev restart`
- Reset database: `ddev exec bin/console doctrine:database:drop --force && ddev exec bin/console doctrine:database:create && ddev exec bin/console doctrine:migrations:migrate`

Generate and maintain type-safe Doctrine entities for robust database interactions in your Symfony application.

## Next Steps

After creating an entity, consider:
- `/migration` — Generate a database migration for your entity changes
- `/form-new` — Create a form type for this entity
- `/test-new` — Write unit and integration tests for entity behavior
- `/controller-new` — Create a CRUD controller for this entity
