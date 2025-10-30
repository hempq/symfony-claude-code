---
description: Test API endpoints with automated test generation
model: claude-sonnet-4-5
---

Generate comprehensive API tests for Symfony endpoints following PHPUnit best practices.

## Target

$ARGUMENTS

## Test Strategy for Symfony APIs

Create practical, maintainable tests using Symfony's testing tools:

### 1. **Testing Approach**
- **Unit tests** for validation logic and services
- **Functional tests** for full API flow (controller → response)
- **Integration tests** for database operations
- Edge case coverage
- Error scenario testing

### 2. **Symfony Testing Tools**
- **PHPUnit** - PHP testing framework (built-in)
- **WebTestCase** - HTTP client for functional tests
- **KernelTestCase** - Service and database testing
- **DataFixtures** - Test data generation
- **ApiTestCase** (API Platform) - REST API testing

### 3. **Test Coverage**

**Happy Paths**
- Valid inputs return expected results
- Proper HTTP status codes (200, 201, 204)
- Correct JSON response structure
- Database persistence verification

**Error Paths**
- Invalid input validation (400)
- Authentication failures (401)
- Authorization failures (403)
- Not found errors (404)
- Rate limiting (429)
- Server errors (500)
- Missing required fields

**Edge Cases**
- Empty requests
- Malformed JSON
- Large payloads
- Special characters
- SQL injection attempts
- XSS attempts
- CSRF protection

### 4. **Test Structure**

```php
<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserControllerTest extends WebTestCase
{
    public function testSuccessfulUserCreation(): void
    {
        // Arrange
        $client = static::createClient();

        // Act
        $client->request('POST', '/api/users', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'email' => 'test@example.com',
                'name' => 'Test User',
            ],
        ]);

        // Assert
        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['status' => 'success']);
    }

    public function testValidationErrors(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/users', [
            'json' => ['email' => 'invalid-email'],
        ]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertJsonContains([
            'type' => 'validation_error',
        ]);
    }
}
```

### 5. **What to Generate**

1. **Functional Test File** - `tests/Controller/Api/[Name]ControllerTest.php`
2. **Unit Tests** - `tests/Service/[Name]ServiceTest.php` (if services exist)
3. **Fixtures** - `tests/Fixtures/[Name]Fixtures.php` (test data)
4. **Helper Traits** - Reusable authentication/setup helpers
5. **DataProviders** - Test case variations

## Symfony Testing Patterns

### **Functional API Test** (Testing Controllers)

```php
<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ProductControllerTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testGetProducts(): void
    {
        $this->client->request('GET', '/api/products');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $this->assertJsonContains(['data' => []]);
    }

    public function testCreateProduct(): void
    {
        $this->client->request('POST', '/api/products', [
            'json' => [
                'name' => 'Test Product',
                'price' => 99.99,
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Test Product', $data['data']['name']);
    }

    public function testUpdateProduct(): void
    {
        // Create product first
        $this->client->request('POST', '/api/products', [
            'json' => ['name' => 'Original', 'price' => 50.00],
        ]);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $productId = $data['data']['id'];

        // Update it
        $this->client->request('PUT', "/api/products/{$productId}", [
            'json' => ['name' => 'Updated', 'price' => 75.00],
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testDeleteProduct(): void
    {
        // Create product
        $this->client->request('POST', '/api/products', [
            'json' => ['name' => 'To Delete', 'price' => 10.00],
        ]);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $productId = $data['data']['id'];

        // Delete it
        $this->client->request('DELETE', "/api/products/{$productId}");

        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        // Verify it's gone
        $this->client->request('GET', "/api/products/{$productId}");
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
```

### **Unit Test for Service**

```php
<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\ProductService;
use App\Repository\ProductRepository;
use PHPUnit\Framework\TestCase;

class ProductServiceTest extends TestCase
{
    public function testCalculateDiscount(): void
    {
        $repository = $this->createMock(ProductRepository::class);
        $service = new ProductService($repository);

        $discountedPrice = $service->calculateDiscount(100.00, 10);

        $this->assertEquals(90.00, $discountedPrice);
    }
}
```

### **Database Testing with Fixtures**

```php
<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use App\DataFixtures\ProductFixtures;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProductWithDataTest extends WebTestCase
{
    private $client;
    private $databaseTool;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        /** @var DatabaseToolCollection $databaseToolCollection */
        $databaseToolCollection = static::getContainer()->get(DatabaseToolCollection::class);
        $this->databaseTool = $databaseToolCollection->get();
    }

    public function testListProductsWithFixtures(): void
    {
        // Load fixtures
        $this->databaseTool->loadFixtures([ProductFixtures::class]);

        $this->client->request('GET', '/api/products');

        $this->assertResponseIsSuccessful();

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertGreaterThan(0, count($data['data']));
    }
}
```

## Testing Authentication & Authorization

### **Authenticated Requests**

```php
public function testAuthenticatedEndpoint(): void
{
    $client = static::createClient();

    // Login or create token
    $client->request('POST', '/api/login', [
        'json' => [
            'email' => 'user@example.com',
            'password' => 'password123',
        ],
    ]);

    $data = json_decode($client->getResponse()->getContent(), true);
    $token = $data['token'];

    // Use token for authenticated request
    $client->request('GET', '/api/protected', [
        'headers' => ['Authorization' => "Bearer {$token}"],
    ]);

    $this->assertResponseIsSuccessful();
}
```

### **Login as User (Symfony 6.1+)**

```php
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

public function testProtectedEndpointAsUser(): void
{
    $client = static::createClient();

    // Get user from database
    $userRepository = static::getContainer()->get(UserRepository::class);
    $user = $userRepository->findOneByEmail('admin@example.com');

    // Login as user
    $client->loginUser($user);

    $client->request('GET', '/api/admin/users');

    $this->assertResponseIsSuccessful();
}
```

### **Security Voter Testing**

```php
use App\Security\ProductVoter;
use App\Entity\User;
use App\Entity\Product;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class ProductVoterTest extends TestCase
{
    public function testUserCanEditOwnProduct(): void
    {
        $user = new User();
        $product = new Product();
        $product->setOwner($user);

        $voter = new ProductVoter();
        $decision = $voter->vote(
            $this->createMock(TokenInterface::class),
            $product,
            ['EDIT']
        );

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $decision);
    }
}
```

## Validation Testing

```php
/**
 * @dataProvider invalidDataProvider
 */
public function testValidationErrors(array $invalidData, string $expectedError): void
{
    $client = static::createClient();

    $client->request('POST', '/api/users', ['json' => $invalidData]);

    $this->assertResponseStatusCodeSame(400);
    $this->assertStringContainsString($expectedError,
        $client->getResponse()->getContent()
    );
}

public function invalidDataProvider(): \Generator
{
    yield 'missing email' => [
        ['name' => 'John'],
        'email',
    ];

    yield 'invalid email format' => [
        ['email' => 'not-an-email', 'name' => 'John'],
        'valid email',
    ];

    yield 'name too short' => [
        ['email' => 'test@test.com', 'name' => 'A'],
        'at least 2 characters',
    ];
}
```

## DDEV Integration

### **Running Tests**

```bash
# Run all tests
ddev exec bin/phpunit

# Run specific test file
ddev exec bin/phpunit tests/Controller/Api/UserControllerTest.php

# Run specific test method
ddev exec bin/phpunit --filter testCreateUser

# Run with coverage
ddev exec bin/phpunit --coverage-html var/coverage

# Run only functional tests
ddev exec bin/phpunit tests/Controller

# Run with verbose output
ddev exec bin/phpunit --testdox
```

### **Test Database Setup**

```bash
# Create test database
ddev exec bin/console --env=test doctrine:database:create

# Run migrations for test DB
ddev exec bin/console --env=test doctrine:migrations:migrate --no-interaction

# Load fixtures for testing
ddev exec bin/console --env=test doctrine:fixtures:load --no-interaction
```

## Key Testing Principles

- ✅ Test behavior, not implementation
- ✅ Clear, descriptive test names (`testUserCannotDeleteOthersProducts`)
- ✅ Arrange-Act-Assert pattern
- ✅ Independent tests (no shared state)
- ✅ Fast execution (<5s for unit tests)
- ✅ Realistic test data with fixtures
- ✅ Test error messages and validation
- ❌ Don't test Symfony framework internals
- ❌ Don't test Doctrine ORM behavior
- ❌ Avoid brittle tests (testing implementation details)

## Additional Scenarios to Cover

1. **Authentication/Authorization**
   - Valid authentication tokens
   - Expired/invalid tokens (401)
   - Missing authentication (401)
   - Insufficient permissions (403)
   - Role-based access control

2. **Data Validation**
   - Type mismatches (string vs int)
   - Out of range values
   - SQL injection attempts (Doctrine protects)
   - XSS payloads (Twig auto-escapes)
   - Required field validation
   - Custom constraint validation

3. **Rate Limiting**
   - Within rate limits
   - Exceeding rate limits (429)
   - Different limits per user/role

4. **Performance**
   - Response times (<200ms for simple endpoints)
   - Large dataset handling (pagination)
   - N+1 query prevention
   - Query count assertions

5. **Error Handling**
   - 404 Not Found
   - 500 Internal Server Error
   - Validation errors (400)
   - Proper error response format

## Best Practices

### **Test Organization**
- Mirror source directory structure in tests/
- One test class per controller
- Group related tests with test methods
- Use data providers for similar test cases

### **Database Management**
- Use separate test database (`.env.test`)
- Reset database between tests
- Use fixtures for consistent test data
- Transaction rollback for isolated tests

### **Assertions**
- Use specific assertions (`assertResponseStatusCodeSame(201)`)
- Test JSON structure with `assertJsonContains()`
- Verify database state after operations
- Check response headers

### **Performance**
- Keep unit tests fast (mock external dependencies)
- Use in-memory SQLite for faster tests
- Limit functional tests to critical paths
- Profile slow tests

Generate production-ready PHPUnit tests that can be run immediately with `ddev exec bin/phpunit`.
