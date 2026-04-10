---
description: Create a new Symfony Messenger handler for async tasks and background jobs
model: claude-sonnet-4-6
effort: high
---

Create a new Symfony Messenger message and handler for asynchronous task processing.

## Task Specification

$ARGUMENTS

## Symfony Messenger Overview

Symfony Messenger provides:
- Asynchronous message/command handling
- Queue-based task processing
- Event-driven architecture
- Multiple transport options (Doctrine, Redis, AMQP, etc.)
- Retries and failure handling
- Middleware pipeline

## Create Message & Handler

### 1. **Create Message Class**

```php
<?php

declare(strict_types=1);

namespace App\Message;

readonly class SendWelcomeEmail
{
    public function __construct(
        public int $userId,
        public string $email,
    ) {}
}
```

### 2. **Create Handler**

```php
<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\SendWelcomeEmail;
use App\Repository\UserRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;

#[AsMessageHandler]
class SendWelcomeEmailHandler
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly UserRepository $userRepository,
        private readonly LoggerInterface $logger,
    ) {}

    public function __invoke(SendWelcomeEmail $message): void
    {
        $this->logger->info('Processing welcome email', [
            'userId' => $message->userId,
        ]);

        $user = $this->userRepository->find($message->userId);

        if (!$user) {
            $this->logger->warning('User not found for welcome email', [
                'userId' => $message->userId,
            ]);
            return;
        }

        $email = (new Email())
            ->from('noreply@example.com')
            ->to($message->email)
            ->subject('Welcome!')
            ->html('<p>Welcome to our application!</p>');

        $this->mailer->send($email);

        $this->logger->info('Welcome email sent', [
            'userId' => $message->userId,
        ]);
    }
}
```

### 3. **Dispatch Message**

```php
<?php

namespace App\Controller;

use App\Message\SendWelcomeEmail;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UserController extends AbstractController
{
    #[Route('/register', methods: ['POST'])]
    public function register(
        MessageBusInterface $messageBus,
        // ... other dependencies
    ): Response {
        // Create user...

        // Dispatch async message
        $messageBus->dispatch(new SendWelcomeEmail(
            userId: $user->getId(),
            email: $user->getEmail(),
        ));

        return $this->json(['message' => 'User registered']);
    }
}
```

## Common Message Patterns

### **Simple Command Message**
```php
readonly class ProcessPayment
{
    public function __construct(
        public string $paymentId,
        public int $amount,
    ) {}
}
```

### **Event Message** (for event-driven architecture)
```php
readonly class UserRegistered
{
    public function __construct(
        public int $userId,
        public \DateTimeImmutable $occurredAt,
    ) {}
}

// Multiple handlers can process the same event
#[AsMessageHandler]
class SendWelcomeEmailOnUserRegistered
{
    public function __invoke(UserRegistered $event): void { }
}

#[AsMessageHandler]
class CreateUserProfileOnUserRegistered
{
    public function __invoke(UserRegistered $event): void { }
}
```

### **Scheduled/Recurring Task**
```php
readonly class CleanupOldRecords
{
    public function __construct(
        public \DateTimeImmutable $before,
    ) {}
}

// Dispatch from console command or cron
#[AsCommand(name: 'app:cleanup')]
class CleanupCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->messageBus->dispatch(new CleanupOldRecords(
            before: new \DateTimeImmutable('-30 days')
        ));
        return Command::SUCCESS;
    }
}
```

### **API Webhook Handler**
```php
readonly class ProcessStripeWebhook
{
    public function __construct(
        public string $eventType,
        public array $payload,
    ) {}
}
```

## Configuration

### **Transports** (`config/packages/messenger.yaml`)

```yaml
framework:
    messenger:
        failure_transport: failed

        transports:
            # Async transport using Doctrine (database queue)
            async:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                options:
                    auto_setup: true
                retry_strategy:
                    max_retries: 3
                    delay: 1000 # ms
                    multiplier: 2
                    max_delay: 0

            # Sync transport (processes immediately)
            sync: 'sync://'

            # Failed messages
            failed: 'doctrine://default?queue_name=failed'

        routing:
            # Route messages to transports
            'App\Message\SendWelcomeEmail': async
            'App\Message\ProcessPayment': async
            'App\Message\SyncOperation': sync
```

### **Transport Options**

**Doctrine** (default, good for DDEV)
```env
# .env
MESSENGER_TRANSPORT_DSN=doctrine://default
```

**Redis** (better performance)
```env
MESSENGER_TRANSPORT_DSN=redis://localhost:6379/messages
```

**AMQP/RabbitMQ** (production scale)
```env
MESSENGER_TRANSPORT_DSN=amqp://guest:guest@localhost:5672/%2f/messages
```

## Running Workers (DDEV)

### **Start Consumer**
```bash
# Process messages from async transport
ddev exec bin/console messenger:consume async -vv

# Process with time limit
ddev exec bin/console messenger:consume async --time-limit=3600

# Process with memory limit
ddev exec bin/console messenger:consume async --memory-limit=128M

# Multiple transports
ddev exec bin/console messenger:consume async failed -vv
```

### **Production Setup with Supervisor**

```ini
; /etc/supervisor/conf.d/messenger-worker.conf
[program:messenger-consume]
command=php /var/www/html/bin/console messenger:consume async --time-limit=3600
user=www-data
numprocs=2
startsecs=0
autostart=true
autorestart=true
process_name=%(program_name)s_%(process_num)02d
```

## Advanced Patterns

### **Stamp/Metadata**
```php
use Symfony\Component\Messenger\Stamp\DelayStamp;

// Delay message by 10 seconds
$messageBus->dispatch(
    new SendWelcomeEmail($userId, $email),
    [new DelayStamp(10000)]
);
```

### **Priority Messages**
```php
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;

$messageBus->dispatch(
    new UrgentTask(),
    [new TransportNamesStamp('high_priority')]
);
```

### **Retry Strategy**
```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        transports:
            async:
                retry_strategy:
                    max_retries: 3
                    delay: 1000 # 1 second
                    multiplier: 2 # doubles each retry
                    max_delay: 10000 # max 10 seconds
```

### **Custom Middleware**
```php
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\StackInterface;

class LoggingMiddleware implements MiddlewareInterface
{
    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $this->logger->info('Processing message', [
            'message' => get_class($envelope->getMessage()),
        ]);

        return $stack->next()->handle($envelope, $stack);
    }
}
```

## Testing Messages

### **Unit Test Handler**
```php
use PHPUnit\Framework\TestCase;
use App\Message\SendWelcomeEmail;
use App\MessageHandler\SendWelcomeEmailHandler;

class SendWelcomeEmailHandlerTest extends TestCase
{
    public function testHandlerSendsEmail(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())->method('send');

        $handler = new SendWelcomeEmailHandler(
            $mailer,
            $this->userRepository,
            $this->logger
        );

        $message = new SendWelcomeEmail(1, 'test@example.com');
        $handler($message);
    }
}
```

### **Integration Test with Messenger**
```php
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Transport\InMemoryTransport;

class MessengerIntegrationTest extends KernelTestCase
{
    public function testMessageIsDispatched(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        /** @var InMemoryTransport $transport */
        $transport = $container->get('messenger.transport.async');

        $messageBus = $container->get('messenger.bus.default');
        $messageBus->dispatch(new SendWelcomeEmail(1, 'test@example.com'));

        $this->assertCount(1, $transport->getSent());
    }
}
```

## Monitoring & Debugging

### **View Failed Messages**
```bash
# List failed messages
ddev exec bin/console messenger:failed:show

# Retry specific failed message
ddev exec bin/console messenger:failed:retry 123

# Retry all failed messages
ddev exec bin/console messenger:failed:retry -vv

# Remove failed message
ddev exec bin/console messenger:failed:remove 123
```

### **Stats & Monitoring**
```bash
# Count messages in queue (Doctrine transport)
ddev exec bin/console dbal:run-sql "SELECT COUNT(*) FROM messenger_messages"

# Monitor in real-time
ddev exec watch -n 1 bin/console messenger:stats
```

## Best Practices

### **Message Design**
- Messages should be immutable (use readonly classes)
- Keep messages simple (pure data containers)
- Use type hints for all properties
- Make messages serializable (avoid resources, closures)
- Version messages if schema might change

### **Handler Design**
- One handler per message (unless using events)
- Handlers should be idempotent (safe to retry)
- Use dependency injection
- Log important events
- Handle edge cases gracefully

### **Performance**
- Use async transport for slow operations (emails, API calls)
- Use sync transport for fast operations (cache updates)
- Set appropriate time/memory limits
- Scale workers based on queue depth
- Monitor queue size

### **Error Handling**
- Configure retry strategy appropriately
- Log errors with context
- Use failed transport for manual review
- Alert on critical failures
- Make handlers resilient to missing data

### **DDEV Integration**
- Use Doctrine transport by default (no extra services)
- Run workers with `ddev exec`
- Monitor logs with `ddev logs -f`
- Test locally before deploying
- Use ddev-contrib for Redis/RabbitMQ if needed

## Complete Example: Image Processing

**Message**
```php
<?php

declare(strict_types=1);

namespace App\Message;

readonly class ProcessUploadedImage
{
    public function __construct(
        public int $imageId,
        public string $filePath,
    ) {}
}
```

**Handler**
```php
<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\ProcessUploadedImage;
use App\Service\ImageProcessor;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Psr\Log\LoggerInterface;

#[AsMessageHandler]
class ProcessUploadedImageHandler
{
    public function __construct(
        private readonly ImageProcessor $imageProcessor,
        private readonly LoggerInterface $logger,
    ) {}

    public function __invoke(ProcessUploadedImage $message): void
    {
        $this->logger->info('Processing image', [
            'imageId' => $message->imageId,
        ]);

        try {
            // Generate thumbnails
            $this->imageProcessor->createThumbnail($message->filePath);

            // Optimize original
            $this->imageProcessor->optimize($message->filePath);

            // Extract metadata
            $this->imageProcessor->extractMetadata($message->filePath);

            $this->logger->info('Image processed successfully', [
                'imageId' => $message->imageId,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Image processing failed', [
                'imageId' => $message->imageId,
                'error' => $e->getMessage(),
            ]);
            throw $e; // Will trigger retry
        }
    }
}
```

Generate production-ready Symfony Messenger messages and handlers for robust async task processing in DDEV environments.

## Next Steps

After creating a message handler, consider:
- `/middleware-new` — Create custom middleware for the handler
- `/test-new` — Write integration tests for message handling
- `/event-new` — Create events dispatched by the handler
