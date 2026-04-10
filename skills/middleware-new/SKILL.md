---
description: Create Messenger middleware for logging, validation, transactions, and audit
model: claude-sonnet-4-6
effort: high
context: fork
argument-hint: "[describe middleware, e.g. 'logging middleware that records message processing time and outcome']"
---

Create Symfony Messenger middleware following best practices.

## Middleware Specification

$ARGUMENTS

## Existing Project Context

Existing middleware:
!`find src/Messenger/Middleware -name "*.php" -type f 2>/dev/null | sort`

Before generating, check existing middleware for ordering and naming conventions.

## What to Generate

Choose the middleware pattern based on requirements:

### 1. **Logging Middleware**

**File:**
- `src/Messenger/Middleware/LoggingMiddleware.php`

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\Messenger\Middleware;

use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Messenger\Stamp\SentStamp;

class LoggingMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $messageClass = get_class($envelope->getMessage());
        $shortName = (new \ReflectionClass($messageClass))->getShortName();

        $context = [
            'message' => $messageClass,
            'stamps' => array_map(
                fn ($stamps) => array_map(fn ($s) => get_class($s), $stamps),
                $envelope->all()
            ),
        ];

        // Determine if dispatching or consuming
        $isReceived = $envelope->last(ReceivedStamp::class) !== null;
        $action = $isReceived ? 'consuming' : 'dispatching';

        $this->logger->info("Messenger: {$action} {$shortName}", $context);

        $startTime = microtime(true);

        try {
            $envelope = $stack->next()->handle($envelope, $stack);

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            $this->logger->info("Messenger: {$shortName} handled successfully", [
                'message' => $messageClass,
                'duration_ms' => $duration,
            ]);

            return $envelope;
        } catch (\Throwable $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            $this->logger->error("Messenger: {$shortName} failed", [
                'message' => $messageClass,
                'duration_ms' => $duration,
                'error' => $e->getMessage(),
                'exception_class' => get_class($e),
            ]);

            throw $e;
        }
    }
}
```

### 2. **Validation Middleware**

**File:**
- `src/Messenger/Middleware/ValidationMiddleware.php`

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\Messenger\Middleware;

use App\Messenger\Stamp\SkipValidationStamp;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\ValidationFailedException;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ValidationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ValidatorInterface $validator,
    ) {}

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        // Skip validation when consuming (already validated on dispatch)
        if ($envelope->last(ReceivedStamp::class) !== null) {
            return $stack->next()->handle($envelope, $stack);
        }

        // Allow skipping validation with a stamp
        if ($envelope->last(SkipValidationStamp::class) !== null) {
            return $stack->next()->handle($envelope, $stack);
        }

        $message = $envelope->getMessage();
        $violations = $this->validator->validate($message);

        if (count($violations) > 0) {
            throw new ValidationFailedException($message, $violations);
        }

        return $stack->next()->handle($envelope, $stack);
    }
}
```

**Custom Stamp** (`src/Messenger/Stamp/SkipValidationStamp.php`):
```php
<?php

declare(strict_types=1);

namespace App\Messenger\Stamp;

use Symfony\Component\Messenger\Stamp\StampInterface;

class SkipValidationStamp implements StampInterface
{
}
```

**Usage with validation constraints on the message:**
```php
<?php

declare(strict_types=1);

namespace App\Message;

use Symfony\Component\Validator\Constraints as Assert;

readonly class CreateOrder
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Positive]
        public int $customerId,

        #[Assert\NotBlank]
        #[Assert\Count(min: 1, minMessage: 'At least one item is required')]
        public array $items,

        #[Assert\PositiveOrZero]
        public float $total,
    ) {}
}
```

### 3. **Transaction Middleware**

**File:**
- `src/Messenger/Middleware/DoctrineTransactionMiddleware.php`

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\Messenger\Middleware;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;

class DoctrineTransactionMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        // Only wrap in transaction when consuming (handler execution)
        if ($envelope->last(ReceivedStamp::class) === null) {
            return $stack->next()->handle($envelope, $stack);
        }

        $messageClass = get_class($envelope->getMessage());

        // Check if entity manager is still open
        if (!$this->em->isOpen()) {
            $this->logger->warning('EntityManager is closed, cannot wrap in transaction', [
                'message' => $messageClass,
            ]);

            return $stack->next()->handle($envelope, $stack);
        }

        $this->em->getConnection()->beginTransaction();

        try {
            $envelope = $stack->next()->handle($envelope, $stack);

            $this->em->flush();
            $this->em->getConnection()->commit();

            $this->logger->debug('Transaction committed', [
                'message' => $messageClass,
            ]);

            return $envelope;
        } catch (\Throwable $e) {
            $this->em->getConnection()->rollBack();

            $this->logger->error('Transaction rolled back', [
                'message' => $messageClass,
                'error' => $e->getMessage(),
            ]);

            // Close entity manager if in an inconsistent state
            if ($this->em->isOpen()) {
                $this->em->close();
            }

            throw $e;
        }
    }
}
```

### 4. **Audit Trail Middleware**

**File:**
- `src/Messenger/Middleware/AuditMiddleware.php`

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\Messenger\Middleware;

use App\Entity\AuditLog;
use App\Messenger\Stamp\AuditableStamp;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class AuditMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly TokenStorageInterface $tokenStorage,
    ) {}

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        // Only audit on dispatch (not on consume)
        if ($envelope->last(ReceivedStamp::class) !== null) {
            return $stack->next()->handle($envelope, $stack);
        }

        // Only audit messages with the AuditableStamp
        $auditStamp = $envelope->last(AuditableStamp::class);
        if ($auditStamp === null) {
            return $stack->next()->handle($envelope, $stack);
        }

        $message = $envelope->getMessage();
        $messageClass = get_class($message);

        $userId = null;
        $token = $this->tokenStorage->getToken();
        if ($token !== null) {
            $user = $token->getUser();
            $userId = $user?->getUserIdentifier();
        }

        $auditLog = new AuditLog();
        $auditLog->setAction($messageClass);
        $auditLog->setPayload(json_encode($message, JSON_THROW_ON_ERROR));
        $auditLog->setUserId($userId);
        $auditLog->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($auditLog);
        $this->em->flush();

        return $stack->next()->handle($envelope, $stack);
    }
}
```

**Auditable Stamp** (`src/Messenger/Stamp/AuditableStamp.php`):
```php
<?php

declare(strict_types=1);

namespace App\Messenger\Stamp;

use Symfony\Component\Messenger\Stamp\StampInterface;

class AuditableStamp implements StampInterface
{
}
```

**Dispatching with audit:**
```php
$this->messageBus->dispatch(
    new CreateOrder($customerId, $items, $total),
    [new AuditableStamp()]
);
```

### 5. **Rate Limiting Middleware**

**File:**
- `src/Messenger/Middleware/RateLimitMiddleware.php`

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\Messenger\Middleware;

use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\RateLimiter\RateLimiterFactory;

class RateLimitMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly RateLimiterFactory $messageLimiter,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        // Only rate-limit on dispatch
        if ($envelope->last(ReceivedStamp::class) !== null) {
            return $stack->next()->handle($envelope, $stack);
        }

        $messageClass = get_class($envelope->getMessage());
        $limiter = $this->messageLimiter->create($messageClass);
        $limit = $limiter->consume(1);

        if (!$limit->isAccepted()) {
            $retryAfter = $limit->getRetryAfter();

            $this->logger->warning('Message rate limited', [
                'message' => $messageClass,
                'retryAfter' => $retryAfter->format('c'),
            ]);

            throw new UnrecoverableMessageHandlingException(
                sprintf('Rate limit exceeded for %s. Retry after %s.', $messageClass, $retryAfter->format('c'))
            );
        }

        return $stack->next()->handle($envelope, $stack);
    }
}
```

**Rate limiter configuration** (`config/packages/rate_limiter.yaml`):
```yaml
framework:
    rate_limiter:
        message_limiter:
            policy: 'sliding_window'
            limit: 100
            interval: '1 minute'
```

## Middleware Configuration

### **Register Middleware** (`config/packages/messenger.yaml`)

```yaml
framework:
    messenger:
        buses:
            messenger.bus.default:
                middleware:
                    # Custom middleware (runs in order listed)
                    - App\Messenger\Middleware\LoggingMiddleware
                    - App\Messenger\Middleware\ValidationMiddleware
                    # Built-in middleware (added automatically, listed here for clarity)
                    # - send_message
                    # - handle_message

        failure_transport: failed

        transports:
            async:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                retry_strategy:
                    max_retries: 3
                    delay: 1000
                    multiplier: 2

            failed: 'doctrine://default?queue_name=failed'

        routing:
            'App\Message\*': async
```

### **Multiple Buses with Different Middleware**

```yaml
framework:
    messenger:
        default_bus: command.bus

        buses:
            command.bus:
                middleware:
                    - App\Messenger\Middleware\LoggingMiddleware
                    - App\Messenger\Middleware\ValidationMiddleware
                    - App\Messenger\Middleware\DoctrineTransactionMiddleware

            event.bus:
                default_middleware:
                    enabled: true
                    allow_no_handlers: true
                middleware:
                    - App\Messenger\Middleware\LoggingMiddleware

            query.bus:
                middleware:
                    - App\Messenger\Middleware\LoggingMiddleware
                    - App\Messenger\Middleware\ValidationMiddleware
```

### **Middleware with Constructor Arguments**

```yaml
services:
    App\Messenger\Middleware\LoggingMiddleware:
        arguments:
            $logger: '@monolog.logger.messenger'

    App\Messenger\Middleware\RateLimitMiddleware:
        arguments:
            $messageLimiter: '@limiter.message_limiter'
```

## Middleware Execution Order

Middleware runs in two phases, wrapping the handler like an onion:

```
Request direction (dispatch) -->

LoggingMiddleware::handle()
    ValidationMiddleware::handle()
        SendMessageMiddleware::handle()      (built-in)
            HandleMessageMiddleware::handle() (built-in)
                --> Handler::__invoke()
            <-- return
        <-- return
    <-- return
<-- return

<-- Response direction (return)
```

The `stack->next()->handle()` call passes control to the next middleware. Code before this call runs on the way in (before handling), and code after runs on the way out (after handling).

## Custom Stamps

Stamps carry metadata through the middleware pipeline:

```php
<?php
// src/Messenger/Stamp/PriorityStamp.php

declare(strict_types=1);

namespace App\Messenger\Stamp;

use Symfony\Component\Messenger\Stamp\StampInterface;

class PriorityStamp implements StampInterface
{
    public function __construct(
        public readonly string $level = 'normal', // low, normal, high, critical
    ) {}
}
```

**Using the stamp in middleware:**
```php
public function handle(Envelope $envelope, StackInterface $stack): Envelope
{
    $priorityStamp = $envelope->last(PriorityStamp::class);
    $priority = $priorityStamp?->level ?? 'normal';

    if ($priority === 'critical') {
        // Handle critical messages differently
        $this->logger->critical('Processing critical message', [
            'message' => get_class($envelope->getMessage()),
        ]);
    }

    return $stack->next()->handle($envelope, $stack);
}
```

## Testing Middleware

### **Unit Test**

```php
<?php

declare(strict_types=1);

namespace App\Tests\Messenger\Middleware;

use App\Message\CreateOrder;
use App\Messenger\Middleware\LoggingMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Test\Middleware\MiddlewareTestCase;

class LoggingMiddlewareTest extends MiddlewareTestCase
{
    public function testLogsMessageDispatching(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->atLeastOnce())
            ->method('info')
            ->with($this->stringContains('dispatching'));

        $middleware = new LoggingMiddleware($logger);

        $envelope = new Envelope(new CreateOrder(1, ['item1'], 99.99));
        $middleware->handle($envelope, $this->getStackMock());
    }

    public function testLogsErrorOnFailure(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('failed'));

        $middleware = new LoggingMiddleware($logger);

        $stack = $this->createMock(StackInterface::class);
        $nextMiddleware = $this->createMock(\Symfony\Component\Messenger\Middleware\MiddlewareInterface::class);
        $nextMiddleware->method('handle')->willThrowException(new \RuntimeException('Handler failed'));
        $stack->method('next')->willReturn($nextMiddleware);

        $this->expectException(\RuntimeException::class);

        $envelope = new Envelope(new CreateOrder(1, ['item1'], 99.99));
        $middleware->handle($envelope, $stack);
    }
}
```

### **Integration Test**

```php
<?php

declare(strict_types=1);

namespace App\Tests\Messenger\Middleware;

use App\Message\CreateOrder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Transport\InMemoryTransport;

class MiddlewareIntegrationTest extends KernelTestCase
{
    public function testMiddlewarePipelineProcessesMessage(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $bus = $container->get(MessageBusInterface::class);

        $bus->dispatch(new CreateOrder(
            customerId: 1,
            items: ['item1'],
            total: 99.99,
        ));

        /** @var InMemoryTransport $transport */
        $transport = $container->get('messenger.transport.async');
        $this->assertCount(1, $transport->getSent());
    }
}
```

## DDEV Commands

```bash
# Start messenger worker (processes messages through middleware pipeline)
ddev exec bin/console messenger:consume async -vv

# Start worker with limits
ddev exec bin/console messenger:consume async --time-limit=3600 --memory-limit=128M

# Debug: list registered middleware for a bus
ddev exec bin/console debug:messenger

# Debug: show message routing
ddev exec bin/console debug:messenger --bus=messenger.bus.default

# View failed messages
ddev exec bin/console messenger:failed:show

# Retry failed messages
ddev exec bin/console messenger:failed:retry

# Clear cache after middleware changes
ddev exec bin/console cache:clear

# Monitor worker logs
ddev logs -f
```

## Best Practices

**Middleware Design**
- Keep middleware focused on a single cross-cutting concern
- Always call `$stack->next()->handle()` to continue the chain
- Handle both dispatch and consume phases appropriately (check `ReceivedStamp`)
- Use try/catch to handle errors without breaking the pipeline
- Return the envelope from the next middleware (do not discard it)

**Performance**
- Avoid expensive operations in middleware that runs on every message
- Use stamps to opt-in to middleware behavior (not every message needs auditing)
- Keep logging middleware lightweight (structured data, not serialization)
- Order middleware so the cheapest checks run first (validation before transactions)

**Error Handling**
- Let exceptions propagate for retry-eligible failures
- Use `UnrecoverableMessageHandlingException` for permanent failures
- Log errors with sufficient context for debugging
- Close the EntityManager if the database is in an inconsistent state

**Configuration**
- List middleware in execution order in `messenger.yaml`
- Use separate buses for different concerns (command bus, event bus, query bus)
- Configure middleware services with tagged loggers for easier filtering
- Test middleware ordering by checking side effects in integration tests

**Testing**
- Use `MiddlewareTestCase` from Symfony for unit tests
- Test both the happy path and error path
- Verify stamps are checked correctly
- Test that middleware properly delegates to the next middleware in the stack

Generate production-ready Symfony Messenger middleware for robust message processing in DDEV environments.

## Next Steps

After creating middleware, consider:
- `/messenger-new` — Create message handlers that use this middleware
- `/test-new` — Write unit tests for middleware logic
