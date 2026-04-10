---
description: Create a Symfony service with business logic
model: claude-sonnet-4-6
effort: high
context: fork
argument-hint: "[describe service, e.g. 'Product import service with CSV parsing and validation']"
---

Create a Symfony service following best practices.

## Service Specification

$ARGUMENTS

## Existing Project Context

Existing services:
!`find src/Service -name "*.php" -type f 2>/dev/null | sort`

Before generating, check existing services for dependency injection patterns, naming conventions, and error handling style.

## What to Generate

Choose the service type based on requirements:

### 1. **Simple Service** (Business Logic)

**File:**
- `src/Service/ProductService.php`

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;

class ProductService
{
    public function __construct(
        private readonly ProductRepository $repository,
        private readonly EntityManagerInterface $em,
    ) {}

    public function createProduct(string $name, float $price): Product
    {
        $product = new Product();
        $product->setName($name);
        $product->setPrice($price);
        $product->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($product);
        $this->em->flush();

        return $product;
    }

    public function updateProduct(Product $product, string $name, float $price): void
    {
        $product->setName($name);
        $product->setPrice($price);
        $product->setUpdatedAt(new \DateTimeImmutable());

        $this->em->flush();
    }

    public function deleteProduct(Product $product): void
    {
        $this->em->remove($product);
        $this->em->flush();
    }

    public function getActiveProducts(): array
    {
        return $this->repository->findBy(
            ['active' => true],
            ['createdAt' => 'DESC']
        );
    }
}
```

### 2. **Service with External API**

**File:**
- `src/Service/WeatherService.php`

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class WeatherService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $apiKey,
    ) {}

    public function getCurrentWeather(string $city): array
    {
        try {
            $response = $this->httpClient->request('GET', 'https://api.weather.com/data', [
                'query' => [
                    'city' => $city,
                    'key' => $this->apiKey,
                ],
                'timeout' => 5,
            ]);

            return $response->toArray();
        } catch (\Exception $e) {
            $this->logger->error('Weather API error', [
                'city' => $city,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Failed to fetch weather data');
        }
    }
}
```

**Configuration** (`config/services.yaml`):
```yaml
services:
    App\Service\WeatherService:
        arguments:
            $apiKey: '%env(WEATHER_API_KEY)%'
```

### 3. **Service with Caching**

**File:**
- `src/Service/ProductCacheService.php`

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\ProductRepository;
use Psr\Cache\CacheItemPoolInterface;

class ProductCacheService
{
    private const CACHE_TTL = 3600; // 1 hour

    public function __construct(
        private readonly ProductRepository $repository,
        private readonly CacheItemPoolInterface $cache,
    ) {}

    public function getPopularProducts(): array
    {
        $cacheKey = 'popular_products';
        $item = $this->cache->getItem($cacheKey);

        if ($item->isHit()) {
            return $item->get();
        }

        // Fetch from database
        $products = $this->repository->findPopular(limit: 10);

        // Cache the result
        $item->set($products);
        $item->expiresAfter(self::CACHE_TTL);
        $this->cache->save($item);

        return $products;
    }

    public function clearCache(): void
    {
        $this->cache->deleteItem('popular_products');
    }
}
```

### 4. **Service with Events**

**File:**
- `src/Service/UserService.php`

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Event\UserCreatedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class UserService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function createUser(string $email, string $plainPassword): User
    {
        $user = new User();
        $user->setEmail($email);

        // Hash password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);

        $this->em->persist($user);
        $this->em->flush();

        // Dispatch event
        $this->eventDispatcher->dispatch(
            new UserCreatedEvent($user),
            UserCreatedEvent::NAME
        );

        return $user;
    }
}
```

**Event** (`src/Event/UserCreatedEvent.php`):
```php
<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class UserCreatedEvent extends Event
{
    public const NAME = 'user.created';

    public function __construct(
        private readonly User $user
    ) {}

    public function getUser(): User
    {
        return $this->user;
    }
}
```

### 5. **Service with File Upload**

**File:**
- `src/Service/FileUploadService.php`

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploadService
{
    public function __construct(
        private readonly SluggerInterface $slugger,
        private readonly string $uploadDirectory,
    ) {}

    public function upload(UploadedFile $file, ?string $directory = null): string
    {
        // Generate unique filename
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        // Move file
        try {
            $targetDirectory = $directory
                ? $this->uploadDirectory . '/' . $directory
                : $this->uploadDirectory;

            $file->move($targetDirectory, $fileName);
        } catch (FileException $e) {
            throw new \RuntimeException('Failed to upload file: ' . $e->getMessage());
        }

        return $directory ? $directory . '/' . $fileName : $fileName;
    }

    public function delete(string $filename): void
    {
        $path = $this->uploadDirectory . '/' . $filename;

        if (file_exists($path)) {
            unlink($path);
        }
    }
}
```

**Configuration** (`config/services.yaml`):
```yaml
services:
    App\Service\FileUploadService:
        arguments:
            $uploadDirectory: '%kernel.project_dir%/public/uploads'
```

### 6. **Service with Email**

**File:**
- `src/Service/EmailService.php`

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class EmailService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $fromEmail,
        private readonly string $fromName,
    ) {}

    public function sendWelcomeEmail(string $toEmail, string $userName): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($toEmail)
            ->subject('Welcome to our platform!')
            ->htmlTemplate('emails/welcome.html.twig')
            ->context([
                'userName' => $userName,
            ]);

        $this->mailer->send($email);
    }

    public function sendPasswordResetEmail(string $toEmail, string $resetToken): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($toEmail)
            ->subject('Reset your password')
            ->htmlTemplate('emails/password_reset.html.twig')
            ->context([
                'resetToken' => $resetToken,
                'expiresAt' => new \DateTimeImmutable('+1 hour'),
            ]);

        $this->mailer->send($email);
    }
}
```

**Configuration** (`config/services.yaml`):
```yaml
services:
    App\Service\EmailService:
        arguments:
            $fromEmail: '%env(MAIL_FROM_ADDRESS)%'
            $fromName: '%env(MAIL_FROM_NAME)%'
```

## Common Service Patterns

### **Service with Validation**
```php
use Symfony\Component\Validator\Validator\ValidatorInterface;

class OrderService
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $em,
    ) {}

    public function createOrder(Order $order): void
    {
        // Validate
        $violations = $this->validator->validate($order);

        if (count($violations) > 0) {
            throw new ValidationException($violations);
        }

        $this->em->persist($order);
        $this->em->flush();
    }
}
```

### **Service with Transactions**
```php
class PaymentService
{
    public function processPayment(Order $order, Payment $payment): void
    {
        $this->em->beginTransaction();

        try {
            // Update order
            $order->setStatus('paid');
            $this->em->persist($order);

            // Save payment
            $payment->setProcessedAt(new \DateTimeImmutable());
            $this->em->persist($payment);

            $this->em->flush();
            $this->em->commit();
        } catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
    }
}
```

### **Service with Messenger**
```php
use Symfony\Component\Messenger\MessageBusInterface;

class NotificationService
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
    ) {}

    public function notifyUser(User $user, string $message): void
    {
        // Dispatch async message
        $this->messageBus->dispatch(
            new SendNotificationMessage($user->getId(), $message)
        );
    }
}
```

### **Service with Multiple Dependencies**
```php
class ReportService
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly UserRepository $userRepository,
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger,
    ) {}

    public function generateSalesReport(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $cacheKey = sprintf('sales_report_%s_%s', $from->format('Y-m-d'), $to->format('Y-m-d'));

        return $this->cache->get($cacheKey, function() use ($from, $to) {
            $this->logger->info('Generating sales report', [
                'from' => $from,
                'to' => $to,
            ]);

            return [
                'orders' => $this->orderRepository->countByDateRange($from, $to),
                'revenue' => $this->orderRepository->sumRevenueByDateRange($from, $to),
                'customers' => $this->userRepository->countNewCustomers($from, $to),
            ];
        });
    }
}
```

## Controller Usage

### **Inject Service in Controller**
```php
#[Route('/products', name: 'products_')]
class ProductController extends AbstractController
{
    public function __construct(
        private readonly ProductService $productService,
    ) {}

    #[Route('/new', name: 'new', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $product = $this->productService->createProduct(
            name: $request->request->get('name'),
            price: (float) $request->request->get('price')
        );

        return $this->json($product, Response::HTTP_CREATED);
    }
}
```

## Service Configuration

### **Auto-wiring (Default)**
```yaml
# config/services.yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true

    App\Service\:
        resource: '../src/Service/'
```

### **Manual Configuration**
```yaml
services:
    App\Service\ProductService:
        arguments:
            $repository: '@App\Repository\ProductRepository'
            $em: '@doctrine.orm.entity_manager'
```

### **Tagged Services**
```yaml
services:
    App\Service\PaymentProcessor\StripeProcessor:
        tags: ['app.payment_processor']

    App\Service\PaymentProcessor\PayPalProcessor:
        tags: ['app.payment_processor']

    # Inject all tagged services
    App\Service\PaymentService:
        arguments:
            $processors: !tagged_iterator app.payment_processor
```

### **Environment Variables**
```yaml
services:
    App\Service\WeatherService:
        arguments:
            $apiKey: '%env(WEATHER_API_KEY)%'
            $timeout: '%env(int:API_TIMEOUT)%'
```

## Testing Services

### **Unit Test**
```php
<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\ProductService;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class ProductServiceTest extends TestCase
{
    public function testCreateProduct(): void
    {
        $repository = $this->createMock(ProductRepository::class);
        $em = $this->createMock(EntityManagerInterface::class);

        $service = new ProductService($repository, $em);

        $em->expects($this->once())->method('persist');
        $em->expects($this->once())->method('flush');

        $product = $service->createProduct('Test Product', 99.99);

        $this->assertEquals('Test Product', $product->getName());
        $this->assertEquals(99.99, $product->getPrice());
    }
}
```

### **Integration Test**
```php
<?php

declare(strict_types=1);

namespace App\Tests\Service;

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
        $product = $this->productService->createProduct('Test', 50.00);

        $this->assertNotNull($product->getId());
        $this->assertEquals('Test', $product->getName());
    }
}
```

## Advanced Service Patterns

### **Retry Logic**
```php
<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ApiService
{
    private const MAX_RETRIES = 3;
    private const RETRY_DELAY_MS = 1000;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
    ) {}

    public function fetchDataWithRetry(string $url): array
    {
        $attempt = 0;

        while ($attempt < self::MAX_RETRIES) {
            try {
                $response = $this->httpClient->request('GET', $url, [
                    'timeout' => 5,
                ]);

                return $response->toArray();
            } catch (\Exception $e) {
                $attempt++;

                $this->logger->warning('API request failed', [
                    'url' => $url,
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                ]);

                if ($attempt >= self::MAX_RETRIES) {
                    throw new \RuntimeException(
                        "Failed after {$attempt} attempts: " . $e->getMessage()
                    );
                }

                // Exponential backoff
                usleep(self::RETRY_DELAY_MS * 1000 * $attempt);
            }
        }
    }
}
```

### **Circuit Breaker Pattern**
```php
<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ResilientApiService
{
    private const FAILURE_THRESHOLD = 5;
    private const TIMEOUT_SECONDS = 60;
    private const CACHE_KEY = 'circuit_breaker_';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheItemPoolInterface $cache,
        private readonly LoggerInterface $logger,
    ) {}

    public function fetchData(string $service, string $url): array
    {
        $cacheKey = self::CACHE_KEY . $service;
        $item = $this->cache->getItem($cacheKey);

        // Check if circuit is open
        if ($item->isHit()) {
            $data = $item->get();

            if ($data['failures'] >= self::FAILURE_THRESHOLD) {
                $this->logger->error('Circuit breaker open', ['service' => $service]);
                throw new \RuntimeException('Service temporarily unavailable');
            }
        }

        try {
            $response = $this->httpClient->request('GET', $url, [
                'timeout' => 5,
            ]);

            $result = $response->toArray();

            // Reset circuit on success
            $this->cache->deleteItem($cacheKey);

            return $result;
        } catch (\Exception $e) {
            // Record failure
            $failures = $item->isHit() ? $item->get()['failures'] + 1 : 1;

            $item->set(['failures' => $failures]);
            $item->expiresAfter(self::TIMEOUT_SECONDS);
            $this->cache->save($item);

            $this->logger->error('API call failed', [
                'service' => $service,
                'failures' => $failures,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
```

### **Rate Limiting**
```php
<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class RateLimitedService
{
    private const MAX_REQUESTS = 100;
    private const TIME_WINDOW = 3600; // 1 hour

    public function __construct(
        private readonly CacheItemPoolInterface $cache,
    ) {}

    public function checkRateLimit(string $identifier): void
    {
        $cacheKey = "rate_limit_{$identifier}";
        $item = $this->cache->getItem($cacheKey);

        $requests = $item->isHit() ? $item->get() : 0;

        if ($requests >= self::MAX_REQUESTS) {
            throw new TooManyRequestsHttpException(
                self::TIME_WINDOW,
                'Rate limit exceeded'
            );
        }

        $item->set($requests + 1);
        $item->expiresAfter(self::TIME_WINDOW);
        $this->cache->save($item);
    }

    public function performRateLimitedAction(string $userId): void
    {
        $this->checkRateLimit($userId);

        // Perform action...
    }
}
```

### **Batch Processing**
```php
<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class BatchImportService
{
    private const BATCH_SIZE = 100;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
    ) {}

    public function importProducts(array $data): int
    {
        $count = 0;
        $batch = 0;

        foreach ($data as $item) {
            $product = new Product();
            $product->setName($item['name']);
            $product->setPrice($item['price']);

            $this->em->persist($product);

            $count++;
            $batch++;

            // Flush and clear every BATCH_SIZE items
            if ($batch >= self::BATCH_SIZE) {
                $this->em->flush();
                $this->em->clear();

                $this->logger->info('Batch processed', [
                    'count' => $count,
                ]);

                $batch = 0;
            }
        }

        // Flush remaining items
        if ($batch > 0) {
            $this->em->flush();
            $this->em->clear();
        }

        $this->logger->info('Import completed', [
            'total' => $count,
        ]);

        return $count;
    }
}
```

### **Async with Messenger**
```php
<?php

declare(strict_types=1);

namespace App\Service;

use App\Message\ProcessImageMessage;
use Symfony\Component\Messenger\MessageBusInterface;

class ImageService
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
    ) {}

    public function scheduleImageProcessing(int $imageId, string $filePath): void
    {
        // Dispatch async message instead of processing immediately
        $this->messageBus->dispatch(
            new ProcessImageMessage($imageId, $filePath)
        );
    }
}
```

## DDEV Commands

```bash
# No specific maker command for services, create manually

# Check if service is registered
ddev exec bin/console debug:container ProductService

# Check all services
ddev exec bin/console debug:container --show-private

# Test service
ddev exec bin/phpunit tests/Service/ProductServiceTest.php

# Clear cache after changes
ddev exec bin/console cache:clear
```

## Best Practices

**Service Design**
- One responsibility per service
- Use dependency injection
- Type-hint all parameters
- Use readonly properties
- Keep methods focused

**Naming**
- Suffix with "Service" (ProductService)
- Clear method names (createProduct, not create)
- Use interfaces for flexibility

**Dependencies**
- Inject only what you need
- Use constructor injection
- Prefer composition over inheritance

**Error Handling**
- Throw specific exceptions
- Log errors appropriately
- Handle edge cases

**Testing**
- Mock dependencies in unit tests
- Use real services in integration tests
- Test happy and error paths

Generate production-ready Symfony services that work seamlessly in DDEV environments.

## Next Steps

After creating a service, consider:
- `/test-new` — Write unit tests with mocked dependencies
- `/event-new` — Create domain events dispatched by this service
- `/dto-new` — Create DTOs for service input/output
