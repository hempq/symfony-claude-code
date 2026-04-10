---
description: Create PHPUnit tests for Symfony applications
model: claude-sonnet-4-6
effort: medium
context: fork
allowed-tools: [Bash, Read, Edit, Write, Grep, Glob]
paths: ["tests/**/*.php"]
argument-hint: "[describe what to test, e.g. 'Unit tests for ProductService create and update methods']"
---

Create PHPUnit tests following best practices.

## Test Specification

$ARGUMENTS

## Existing Project Context

Existing tests:
!`find tests -name "*Test.php" -type f 2>/dev/null | head -20`

Test config:
!`head -15 phpunit.xml.dist 2>/dev/null || head -15 phpunit.xml 2>/dev/null`

Before generating, follow existing test organization, assertion style, and naming conventions.

## What to Generate

Choose the test type based on requirements:

### 1. **Unit Test** (Test Single Class)

**File:**
- `tests/Service/ProductServiceTest.php`

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Service\ProductService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class ProductServiceTest extends TestCase
{
    private ProductService $service;
    private ProductRepository $repository;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ProductRepository::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->service = new ProductService($this->repository, $this->em);
    }

    public function testCreateProduct(): void
    {
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Product::class));

        $this->em->expects($this->once())
            ->method('flush');

        $product = $this->service->createProduct('Test Product', 99.99);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals('Test Product', $product->getName());
        $this->assertEquals(99.99, $product->getPrice());
    }

    public function testGetActiveProducts(): void
    {
        $expectedProducts = [new Product(), new Product()];

        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['active' => true], ['createdAt' => 'DESC'])
            ->willReturn($expectedProducts);

        $result = $this->service->getActiveProducts();

        $this->assertCount(2, $result);
        $this->assertEquals($expectedProducts, $result);
    }
}
```

### 2. **Integration Test** (Test with Database)

**File:**
- `tests/Integration/ProductServiceIntegrationTest.php`

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\Product;
use App\Service\ProductService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ProductServiceIntegrationTest extends KernelTestCase
{
    private ProductService $productService;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->productService = static::getContainer()->get(ProductService::class);
    }

    public function testCreateProductIntegration(): void
    {
        $product = $this->productService->createProduct('Integration Test', 50.00);

        $this->assertNotNull($product->getId());
        $this->assertEquals('Integration Test', $product->getName());
        $this->assertEquals(50.00, $product->getPrice());
    }

    public function testGetActiveProducts(): void
    {
        // Create test data
        $this->productService->createProduct('Product 1', 10.00);
        $this->productService->createProduct('Product 2', 20.00);

        $products = $this->productService->getActiveProducts();

        $this->assertGreaterThanOrEqual(2, count($products));
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Clean up test data if needed
    }
}
```

### 3. **Functional Test** (Test HTTP Endpoints)

**File:**
- `tests/Controller/ProductControllerTest.php`

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ProductControllerTest extends WebTestCase
{
    public function testListProducts(): void
    {
        $client = static::createClient();
        $client->request('GET', '/products');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Products');
    }

    public function testCreateProduct(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/products', [
            'json' => [
                'name' => 'New Product',
                'price' => 29.99,
                'description' => 'Test description',
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('New Product', $data['name']);
        $this->assertEquals(29.99, $data['price']);
    }

    public function testCreateProductWithInvalidData(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/products', [
            'json' => [
                'name' => '',
                'price' => -10,
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testGetProduct(): void
    {
        $client = static::createClient();

        // Assume product with ID 1 exists
        $client->request('GET', '/api/products/1');

        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('name', $data);
    }

    public function testUpdateProduct(): void
    {
        $client = static::createClient();

        $client->request('PUT', '/api/products/1', [
            'json' => [
                'name' => 'Updated Product',
                'price' => 39.99,
            ],
        ]);

        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Updated Product', $data['name']);
    }

    public function testDeleteProduct(): void
    {
        $client = static::createClient();

        $client->request('DELETE', '/api/products/1');

        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }
}
```

### 4. **Repository Test**

**File:**
- `tests/Repository/ProductRepositoryTest.php`

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ProductRepositoryTest extends KernelTestCase
{
    private ProductRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = static::getContainer()->get(ProductRepository::class);
    }

    public function testFindByPriceRange(): void
    {
        $products = $this->repository->findByPriceRange(10.00, 50.00);

        $this->assertIsArray($products);

        foreach ($products as $product) {
            $this->assertInstanceOf(Product::class, $product);
            $this->assertGreaterThanOrEqual(10.00, $product->getPrice());
            $this->assertLessThanOrEqual(50.00, $product->getPrice());
        }
    }

    public function testFindPopular(): void
    {
        $products = $this->repository->findPopular(limit: 5);

        $this->assertCount(5, $products);
        $this->assertContainsOnlyInstancesOf(Product::class, $products);
    }
}
```

### 5. **Form Test**

**File:**
- `tests/Form/ProductTypeTest.php`

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Entity\Product;
use App\Form\ProductType;
use Symfony\Component\Form\Test\TypeTestCase;

class ProductTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $formData = [
            'name' => 'Test Product',
            'price' => 99.99,
            'description' => 'Test description',
        ];

        $product = new Product();
        $form = $this->factory->create(ProductType::class, $product);

        $expected = new Product();
        $expected->setName('Test Product');
        $expected->setPrice(99.99);
        $expected->setDescription('Test description');

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expected->getName(), $product->getName());
        $this->assertEquals($expected->getPrice(), $product->getPrice());

        $view = $form->createView();
        $children = $view->children;

        foreach (array_keys($formData) as $key) {
            $this->assertArrayHasKey($key, $children);
        }
    }
}
```

### 6. **Entity Test**

**File:**
- `tests/Entity/ProductTest.php`

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Product;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{
    public function testProductCreation(): void
    {
        $product = new Product();
        $product->setName('Test Product');
        $product->setPrice(99.99);
        $product->setDescription('Test description');

        $this->assertEquals('Test Product', $product->getName());
        $this->assertEquals(99.99, $product->getPrice());
        $this->assertEquals('Test description', $product->getDescription());
        $this->assertInstanceOf(\DateTimeImmutable::class, $product->getCreatedAt());
    }

    public function testProductWithInvalidPrice(): void
    {
        $this->expectException(\TypeError::class);

        $product = new Product();
        $product->setPrice('invalid'); // Should fail with strict types
    }
}
```

## Test Data Fixtures

### **Create Test Fixtures**

**File:** `tests/Fixtures/ProductFixtures.php`

```php
<?php

declare(strict_types=1);

namespace App\Tests\Fixtures;

use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ProductFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $product = new Product();
            $product->setName("Product $i");
            $product->setPrice(10.00 * $i);
            $product->setDescription("Description for product $i");

            $manager->persist($product);
        }

        $manager->flush();
    }
}
```

**Load Fixtures:**
```bash
ddev exec bin/console doctrine:fixtures:load --env=test
```

## Test Database Setup

### **Configure Test Database** (`config/packages/test/doctrine.yaml`)

```yaml
doctrine:
    dbal:
        # Use separate test database
        dbname_suffix: '_test'
```

### **Test Environment Setup**

```bash
# Create test database
ddev exec bin/console doctrine:database:create --env=test

# Run migrations on test database
ddev exec bin/console doctrine:migrations:migrate --env=test --no-interaction

# Load fixtures
ddev exec bin/console doctrine:fixtures:load --env=test --no-interaction
```

## Common Assertions

### **Response Assertions**
```php
// Status codes
$this->assertResponseIsSuccessful();
$this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
$this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
$this->assertResponseRedirects('/products');

// Content
$this->assertSelectorTextContains('h1', 'Products');
$this->assertSelectorExists('.product-item');
$this->assertSelectorNotExists('.error-message');
$this->assertJsonContains(['name' => 'Product']);
```

### **PHPUnit Assertions**
```php
// Equality
$this->assertEquals($expected, $actual);
$this->assertSame($expected, $actual);
$this->assertNotEquals($expected, $actual);

// Types
$this->assertInstanceOf(Product::class, $product);
$this->assertIsArray($result);
$this->assertIsString($name);
$this->assertIsBool($active);

// Nullability
$this->assertNull($value);
$this->assertNotNull($value);

// Collections
$this->assertCount(5, $array);
$this->assertEmpty($array);
$this->assertNotEmpty($array);
$this->assertContains('value', $array);
$this->assertContainsOnlyInstancesOf(Product::class, $products);

// Comparison
$this->assertGreaterThan(10, $value);
$this->assertLessThan(100, $value);
$this->assertGreaterThanOrEqual(10, $value);

// Exceptions
$this->expectException(\InvalidArgumentException::class);
$this->expectExceptionMessage('Invalid input');
```

## Mocking & Stubs

### **Mock Services**
```php
$mailer = $this->createMock(MailerInterface::class);
$mailer->expects($this->once())
    ->method('send')
    ->with($this->isInstanceOf(Email::class));
```

### **Stub Methods**
```php
$repository = $this->createStub(ProductRepository::class);
$repository->method('find')
    ->willReturn(new Product());
```

### **Configure Mock**
```php
$service = $this->createMock(ProductService::class);
$service->expects($this->exactly(2))
    ->method('createProduct')
    ->withConsecutive(
        ['Product 1', 10.00],
        ['Product 2', 20.00]
    )
    ->willReturnOnConsecutiveCalls(
        new Product(),
        new Product()
    );
```

## Authentication in Tests

### **Authenticate User**
```php
use App\Entity\User;

public function testAuthenticatedEndpoint(): void
{
    $client = static::createClient();

    $user = new User();
    $user->setEmail('test@example.com');
    $user->setRoles(['ROLE_USER']);

    $client->loginUser($user);

    $client->request('GET', '/profile');
    $this->assertResponseIsSuccessful();
}
```

### **Test with Admin Role**
```php
public function testAdminOnlyEndpoint(): void
{
    $client = static::createClient();

    $admin = new User();
    $admin->setEmail('admin@example.com');
    $admin->setRoles(['ROLE_ADMIN']);

    $client->loginUser($admin);

    $client->request('GET', '/admin/dashboard');
    $this->assertResponseIsSuccessful();
}
```

## Test Coverage

### **Generate Coverage Report**
```bash
# HTML report
ddev exec vendor/bin/phpunit --coverage-html coverage

# Text report
ddev exec vendor/bin/phpunit --coverage-text

# Clover XML (for CI)
ddev exec vendor/bin/phpunit --coverage-clover coverage.xml
```

### **Coverage Configuration** (`phpunit.xml.dist`)
```xml
<coverage>
    <include>
        <directory suffix=".php">src</directory>
    </include>
    <exclude>
        <directory>src/DataFixtures</directory>
        <directory>src/Kernel.php</directory>
    </exclude>
</coverage>
```

## Running Tests (DDEV)

```bash
# Run all tests
ddev exec bin/phpunit

# Run specific test file
ddev exec bin/phpunit tests/Service/ProductServiceTest.php

# Run specific test method
ddev exec bin/phpunit --filter testCreateProduct

# Run tests with coverage
ddev exec vendor/bin/phpunit --coverage-text

# Run tests in specific directory
ddev exec bin/phpunit tests/Controller

# Run with verbose output
ddev exec bin/phpunit -v

# Stop on first failure
ddev exec bin/phpunit --stop-on-failure

# Run tests in parallel (with paratest)
ddev exec vendor/bin/paratest
```

## Best Practices

**Test Organization**
- Mirror directory structure (tests/Service matches src/Service)
- One test class per production class
- Group related tests with data providers
- Use descriptive test method names

**Test Isolation**
- Each test should be independent
- Use setUp() and tearDown() for common setup/cleanup
- Don't rely on test execution order
- Reset database state between tests

**Test Quality**
- Test both happy path and edge cases
- Test error conditions
- Aim for high coverage (80%+ on critical code)
- Keep tests simple and readable

**Performance**
- Use unit tests for speed (no database)
- Use integration tests for critical paths
- Mock external services (APIs, email)
- Use in-memory database for fast tests

**Naming Conventions**
- test{MethodName}{Scenario}
- testCreateProduct()
- testCreateProductWithInvalidData()
- testGetProductNotFound()

Generate comprehensive PHPUnit tests for reliable Symfony applications in DDEV environments.
