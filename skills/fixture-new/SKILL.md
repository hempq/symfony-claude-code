---
description: Create Doctrine fixtures for test and seed data
model: claude-sonnet-4-6
effort: high
paths: ["src/DataFixtures/**/*.php"]
argument-hint: "[describe fixtures, e.g. 'User fixtures with admin, editor, and regular users plus sample blog posts']"
---

Create Doctrine fixtures for test and seed data following best practices.

## Fixture Specification

$ARGUMENTS

## Existing Project Context

Existing entities:
!`find src/Entity -name "*.php" -type f 2>/dev/null | sort`

Existing fixtures:
!`find src/DataFixtures -name "*.php" -type f 2>/dev/null | sort`

Before generating, check existing entities for required fields and relationships.

## What to Generate

Choose the fixture pattern based on requirements:

### 1. **Simple Fixture** (Single Entity)

**File:**
- `src/DataFixtures/UserFixtures.php`

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {}

    public function load(ObjectManager $manager): void
    {
        $admin = new User();
        $admin->setEmail('admin@example.com');
        $admin->setName('Admin User');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $admin->setCreatedAt(new \DateTimeImmutable());
        $manager->persist($admin);

        $editor = new User();
        $editor->setEmail('editor@example.com');
        $editor->setName('Editor User');
        $editor->setRoles(['ROLE_EDITOR']);
        $editor->setPassword($this->passwordHasher->hashPassword($editor, 'editor123'));
        $editor->setCreatedAt(new \DateTimeImmutable());
        $manager->persist($editor);

        // Create regular users
        for ($i = 1; $i <= 10; $i++) {
            $user = new User();
            $user->setEmail("user{$i}@example.com");
            $user->setName("User {$i}");
            $user->setRoles(['ROLE_USER']);
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
            $user->setCreatedAt(new \DateTimeImmutable("-{$i} days"));
            $manager->persist($user);
        }

        $manager->flush();
    }
}
```

### 2. **Fixture with References** (Related Entities)

**Files:**
- `src/DataFixtures/CategoryFixtures.php`
- `src/DataFixtures/ProductFixtures.php`

**Category Fixture:**
```php
<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CategoryFixtures extends Fixture
{
    public const CATEGORY_ELECTRONICS = 'category-electronics';
    public const CATEGORY_BOOKS = 'category-books';
    public const CATEGORY_CLOTHING = 'category-clothing';

    public function load(ObjectManager $manager): void
    {
        $categories = [
            self::CATEGORY_ELECTRONICS => ['name' => 'Electronics', 'slug' => 'electronics'],
            self::CATEGORY_BOOKS => ['name' => 'Books', 'slug' => 'books'],
            self::CATEGORY_CLOTHING => ['name' => 'Clothing', 'slug' => 'clothing'],
        ];

        foreach ($categories as $reference => $data) {
            $category = new Category();
            $category->setName($data['name']);
            $category->setSlug($data['slug']);

            $manager->persist($category);

            // Store reference for other fixtures to use
            $this->addReference($reference, $category);
        }

        $manager->flush();
    }
}
```

**Product Fixture (depends on Category):**
```php
<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Product;
use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ProductFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var Category $electronics */
        $electronics = $this->getReference(CategoryFixtures::CATEGORY_ELECTRONICS, Category::class);

        /** @var Category $books */
        $books = $this->getReference(CategoryFixtures::CATEGORY_BOOKS, Category::class);

        $products = [
            ['name' => 'Laptop', 'price' => 999.99, 'category' => $electronics],
            ['name' => 'Smartphone', 'price' => 699.99, 'category' => $electronics],
            ['name' => 'Headphones', 'price' => 149.99, 'category' => $electronics],
            ['name' => 'PHP Design Patterns', 'price' => 39.99, 'category' => $books],
            ['name' => 'Symfony Best Practices', 'price' => 44.99, 'category' => $books],
        ];

        foreach ($products as $data) {
            $product = new Product();
            $product->setName($data['name']);
            $product->setPrice($data['price']);
            $product->setCategory($data['category']);
            $product->setCreatedAt(new \DateTimeImmutable());

            $manager->persist($product);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            CategoryFixtures::class,
        ];
    }
}
```

### 3. **Fixture with Multiple References** (Complex Relationships)

**File:**
- `src/DataFixtures/PostFixtures.php`

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Post;
use App\Entity\Tag;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class PostFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Create tags first
        $tagNames = ['php', 'symfony', 'doctrine', 'twig', 'javascript'];
        $tags = [];

        foreach ($tagNames as $tagName) {
            $tag = new Tag();
            $tag->setName($tagName);
            $tag->setSlug($tagName);
            $manager->persist($tag);
            $tags[$tagName] = $tag;
        }

        /** @var User $admin */
        $admin = $this->getReference(UserFixtures::ADMIN_USER, User::class);

        /** @var User $editor */
        $editor = $this->getReference(UserFixtures::EDITOR_USER, User::class);

        $posts = [
            [
                'title' => 'Getting Started with Symfony',
                'body' => 'Symfony is a powerful PHP framework...',
                'author' => $admin,
                'tags' => [$tags['php'], $tags['symfony']],
                'published' => true,
            ],
            [
                'title' => 'Doctrine ORM Tips',
                'body' => 'Doctrine provides a powerful ORM...',
                'author' => $editor,
                'tags' => [$tags['php'], $tags['doctrine']],
                'published' => true,
            ],
            [
                'title' => 'Twig Template Guide',
                'body' => 'Twig is the default template engine...',
                'author' => $admin,
                'tags' => [$tags['symfony'], $tags['twig']],
                'published' => false,
            ],
        ];

        foreach ($posts as $data) {
            $post = new Post();
            $post->setTitle($data['title']);
            $post->setBody($data['body']);
            $post->setAuthor($data['author']);
            $post->setPublished($data['published']);
            $post->setCreatedAt(new \DateTimeImmutable());

            foreach ($data['tags'] as $tag) {
                $post->addTag($tag);
            }

            $manager->persist($post);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
}
```

### 4. **Fixture Groups** (Selective Loading)

**File:**
- `src/DataFixtures/TestFixtures.php`

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class TestFixtures extends Fixture implements FixtureGroupInterface
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {}

    public static function getGroups(): array
    {
        return ['test', 'ci'];
    }

    public function load(ObjectManager $manager): void
    {
        // Minimal data for test environments
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setName('Test User');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'test'));
        $user->setCreatedAt(new \DateTimeImmutable());

        $manager->persist($user);
        $manager->flush();
    }
}
```

```bash
# Load only test group fixtures
ddev exec bin/console doctrine:fixtures:load --group=test

# Load only CI group
ddev exec bin/console doctrine:fixtures:load --group=ci
```

### 5. **Fixture with Randomized Data** (Faker-style)

**File:**
- `src/DataFixtures/CustomerFixtures.php`

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Customer;
use App\Entity\Address;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CustomerFixtures extends Fixture
{
    private const FIRST_NAMES = ['Alice', 'Bob', 'Charlie', 'Diana', 'Eve', 'Frank', 'Grace', 'Henry'];
    private const LAST_NAMES = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis'];
    private const CITIES = ['Berlin', 'Munich', 'Hamburg', 'Frankfurt', 'Cologne', 'Stuttgart'];

    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < 50; $i++) {
            $firstName = self::FIRST_NAMES[array_rand(self::FIRST_NAMES)];
            $lastName = self::LAST_NAMES[array_rand(self::LAST_NAMES)];

            $customer = new Customer();
            $customer->setFirstName($firstName);
            $customer->setLastName($lastName);
            $customer->setEmail(strtolower("{$firstName}.{$lastName}.{$i}@example.com"));
            $customer->setCreatedAt(new \DateTimeImmutable("-" . random_int(1, 365) . " days"));

            $address = new Address();
            $address->setStreet(random_int(1, 999) . ' Main Street');
            $address->setCity(self::CITIES[array_rand(self::CITIES)]);
            $address->setPostalCode(str_pad((string) random_int(10000, 99999), 5, '0'));
            $address->setCountry('DE');

            $customer->setAddress($address);
            $manager->persist($customer);

            // Store references for other fixtures
            $this->addReference("customer-{$i}", $customer);
        }

        $manager->flush();
    }
}
```

## Foundry Factory Pattern (zenstruck/foundry)

An alternative approach using `zenstruck/foundry` for more flexible fixture generation.

### **Installation**

```bash
ddev composer require --dev zenstruck/foundry
```

### **Create a Factory**

```php
<?php
// src/Factory/UserFactory.php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\User;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<User>
 */
final class UserFactory extends PersistentProxyObjectFactory
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {}

    public static function class(): string
    {
        return User::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'email' => self::faker()->unique()->safeEmail(),
            'name' => self::faker()->name(),
            'roles' => ['ROLE_USER'],
            'createdAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTimeBetween('-1 year')),
        ];
    }

    protected function initialize(): static
    {
        return $this->afterInstantiate(function (User $user): void {
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
        });
    }
}
```

### **Create a Product Factory**

```php
<?php
// src/Factory/ProductFactory.php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\Product;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Product>
 */
final class ProductFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Product::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'name' => self::faker()->words(3, true),
            'description' => self::faker()->paragraph(),
            'price' => self::faker()->randomFloat(2, 9.99, 999.99),
            'sku' => self::faker()->unique()->bothify('SKU-####-??'),
            'active' => self::faker()->boolean(80), // 80% chance active
            'createdAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTimeBetween('-6 months')),
        ];
    }
}
```

### **Use Factories in Fixtures**

```php
<?php
// src/DataFixtures/AppFixtures.php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Factory\CategoryFactory;
use App\Factory\ProductFactory;
use App\Factory\UserFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Create specific users
        UserFactory::createOne([
            'email' => 'admin@example.com',
            'name' => 'Admin User',
            'roles' => ['ROLE_ADMIN'],
        ]);

        UserFactory::createOne([
            'email' => 'editor@example.com',
            'name' => 'Editor User',
            'roles' => ['ROLE_EDITOR'],
        ]);

        // Create 20 random users
        UserFactory::createMany(20);

        // Create categories
        $categories = CategoryFactory::createMany(5);

        // Create 50 products linked to random categories
        ProductFactory::createMany(50, function () use ($categories) {
            return [
                'category' => $categories[array_rand($categories)],
            ];
        });
    }
}
```

### **Use Factories in Tests**

```php
<?php

namespace App\Tests\Service;

use App\Factory\UserFactory;
use App\Factory\ProductFactory;
use App\Service\ProductService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class ProductServiceTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    public function testGetActiveProducts(): void
    {
        // Arrange: create test data inline
        ProductFactory::createMany(3, ['active' => true]);
        ProductFactory::createMany(2, ['active' => false]);

        $service = static::getContainer()->get(ProductService::class);

        // Act
        $activeProducts = $service->getActiveProducts();

        // Assert
        $this->assertCount(3, $activeProducts);
    }

    public function testOwnerCanEditProduct(): void
    {
        $owner = UserFactory::createOne();
        $product = ProductFactory::createOne(['owner' => $owner]);

        $service = static::getContainer()->get(ProductService::class);

        $this->assertTrue($service->canEdit($product->_real(), $owner->_real()));
    }
}
```

### **Factory States (Reusable Configurations)**

```php
final class UserFactory extends PersistentProxyObjectFactory
{
    // ... defaults and class methods ...

    public function admin(): static
    {
        return $this->with([
            'roles' => ['ROLE_ADMIN'],
        ]);
    }

    public function verified(): static
    {
        return $this->with([
            'verifiedAt' => new \DateTimeImmutable(),
        ]);
    }

    public function inactive(): static
    {
        return $this->with([
            'active' => false,
        ]);
    }
}
```

```php
// Usage in tests or fixtures
UserFactory::new()->admin()->verified()->create();
UserFactory::new()->inactive()->createMany(5);
```

## Ordered Fixture Loading

### **Using DependentFixtureInterface**

```php
class OrderFixtures extends Fixture implements DependentFixtureInterface
{
    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            ProductFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        // UserFixtures and ProductFixtures are guaranteed to have run first
    }
}
```

### **Dependency Chain**

```
UserFixtures (no dependencies)
    -> CategoryFixtures (no dependencies)
        -> ProductFixtures (depends on CategoryFixtures)
            -> OrderFixtures (depends on UserFixtures, ProductFixtures)
                -> OrderItemFixtures (depends on OrderFixtures)
```

## AppFixture Pattern (Single Entry Point)

For smaller projects, a single fixture class can be simpler:

```php
<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {}

    public function load(ObjectManager $manager): void
    {
        $this->loadUsers($manager);
        $categories = $this->loadCategories($manager);
        $this->loadProducts($manager, $categories);

        $manager->flush();
    }

    private function loadUsers(ObjectManager $manager): void
    {
        $admin = new User();
        $admin->setEmail('admin@example.com');
        $admin->setName('Admin');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin'));
        $manager->persist($admin);
    }

    /** @return Category[] */
    private function loadCategories(ObjectManager $manager): array
    {
        $categories = [];

        foreach (['Electronics', 'Books', 'Clothing'] as $name) {
            $category = new Category();
            $category->setName($name);
            $category->setSlug(strtolower($name));
            $manager->persist($category);
            $categories[] = $category;
        }

        return $categories;
    }

    /** @param Category[] $categories */
    private function loadProducts(ObjectManager $manager, array $categories): void
    {
        $products = [
            ['name' => 'Laptop', 'price' => 999.99, 'categoryIndex' => 0],
            ['name' => 'PHP Book', 'price' => 39.99, 'categoryIndex' => 1],
            ['name' => 'T-Shirt', 'price' => 24.99, 'categoryIndex' => 2],
        ];

        foreach ($products as $data) {
            $product = new Product();
            $product->setName($data['name']);
            $product->setPrice($data['price']);
            $product->setCategory($categories[$data['categoryIndex']]);
            $product->setCreatedAt(new \DateTimeImmutable());
            $manager->persist($product);
        }
    }
}
```

## DDEV Commands

```bash
# Load all fixtures (purges database first)
ddev exec bin/console doctrine:fixtures:load

# Load without confirmation prompt
ddev exec bin/console doctrine:fixtures:load --no-interaction

# Append fixtures (do not purge existing data)
ddev exec bin/console doctrine:fixtures:load --append

# Load specific group
ddev exec bin/console doctrine:fixtures:load --group=test

# Load multiple groups
ddev exec bin/console doctrine:fixtures:load --group=test --group=demo

# Full reset: drop DB, create, migrate, load fixtures
ddev exec bin/console doctrine:database:drop --force && \
ddev exec bin/console doctrine:database:create && \
ddev exec bin/console doctrine:migrations:migrate --no-interaction && \
ddev exec bin/console doctrine:fixtures:load --no-interaction

# Generate a fixture class with MakerBundle
ddev exec bin/console make:fixtures ProductFixtures

# Clear cache after fixture changes
ddev exec bin/console cache:clear
```

## Best Practices

**Fixture Design**
- Keep fixtures deterministic (same data every run)
- Use constants for reference names to avoid typos
- Implement `DependentFixtureInterface` for cross-fixture references
- Type-hint `getReference()` calls for IDE support
- Keep fixtures small enough to load quickly

**Data Quality**
- Use realistic data that covers edge cases
- Include boundary values (empty strings, zero, max length)
- Create data for all user roles and permission levels
- Include both valid and edge-case scenarios
- Use meaningful emails and names for easy identification

**Organization**
- One fixture class per entity (or logical group)
- Use fixture groups for different environments (dev, test, demo)
- Put shared constants in a dedicated constants class if needed
- Separate seed data (required) from sample data (optional)

**Performance**
- Flush in batches for large datasets (every 100-200 entities)
- Disable SQL logging during fixture loading for speed
- Use `--no-interaction` in CI pipelines
- Consider Foundry factories for test-only data

**Testing Integration**
- Use Foundry factories in tests for inline data creation
- Use `ResetDatabase` trait to ensure clean state between tests
- Keep test fixtures minimal (only what the test needs)
- Prefer factory states over creating many fixture classes

Generate production-ready Doctrine fixtures and Foundry factories for reliable test data in DDEV environments.

## Next Steps

After creating fixtures, consider:
- `/test-new` — Write integration tests that use these fixtures
- `/entity-new` — Create additional entities that need fixtures
