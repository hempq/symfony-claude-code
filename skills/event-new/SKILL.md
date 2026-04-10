---
description: Create domain events with listeners, subscribers, and Doctrine lifecycle hooks
model: claude-sonnet-4-6
effort: high
context: fork
---

Create a Symfony event with listener or subscriber following best practices.

## Event Specification

$ARGUMENTS

## Existing Project Context

Existing events:
!`find src/Event -name "*Event.php" -type f 2>/dev/null | sort`
!`find src/EventListener src/EventSubscriber -name "*.php" -type f 2>/dev/null | sort`

Before generating, follow existing event naming and listener organization.

## What to Generate

Choose between Event Listener (single event) or Event Subscriber (multiple events):

### 1. **Custom Event + Listener**

**Files:**
- `src/Event/UserRegisteredEvent.php` (Event)
- `src/EventListener/UserRegisteredListener.php` (Listener)

**Event:**
```php
<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class UserRegisteredEvent extends Event
{
    public const NAME = 'user.registered';

    public function __construct(
        private readonly User $user,
        private readonly \DateTimeImmutable $registeredAt,
    ) {}

    public function getUser(): User
    {
        return $this->user;
    }

    public function getRegisteredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }
}
```

**Listener:**
```php
<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Event\UserRegisteredEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsEventListener(event: UserRegisteredEvent::NAME)]
class UserRegisteredListener
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
    ) {}

    public function __invoke(UserRegisteredEvent $event): void
    {
        $user = $event->getUser();

        $this->logger->info('User registered', [
            'userId' => $user->getId(),
            'email' => $user->getEmail(),
        ]);

        // Send welcome email
        $email = (new Email())
            ->from('noreply@example.com')
            ->to($user->getEmail())
            ->subject('Welcome!')
            ->html('<p>Welcome to our platform!</p>');

        $this->mailer->send($email);
    }
}
```

**Dispatch Event:**
```php
<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Event\UserRegisteredEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class UserService
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function registerUser(string $email, string $password): User
    {
        $user = new User();
        $user->setEmail($email);
        // ... set other properties

        // Dispatch event
        $this->eventDispatcher->dispatch(
            new UserRegisteredEvent($user, new \DateTimeImmutable()),
            UserRegisteredEvent::NAME
        );

        return $user;
    }
}
```

### 2. **Event Subscriber** (Multiple Events)

**Files:**
- `src/EventSubscriber/ProductEventSubscriber.php`

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\ProductCreatedEvent;
use App\Event\ProductUpdatedEvent;
use App\Event\ProductDeletedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProductEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            ProductCreatedEvent::NAME => 'onProductCreated',
            ProductUpdatedEvent::NAME => ['onProductUpdated', 10], // Priority
            ProductDeletedEvent::NAME => [
                ['logDeletion', 20],
                ['notifyAdmin', 10],
            ],
        ];
    }

    public function onProductCreated(ProductCreatedEvent $event): void
    {
        $this->logger->info('Product created', [
            'productId' => $event->getProduct()->getId(),
            'name' => $event->getProduct()->getName(),
        ]);

        // Additional logic...
    }

    public function onProductUpdated(ProductUpdatedEvent $event): void
    {
        $this->logger->info('Product updated', [
            'productId' => $event->getProduct()->getId(),
        ]);
    }

    public function logDeletion(ProductDeletedEvent $event): void
    {
        $this->logger->warning('Product deleted', [
            'productId' => $event->getProductId(),
        ]);
    }

    public function notifyAdmin(ProductDeletedEvent $event): void
    {
        // Send notification to admin
    }
}
```

### 3. **Doctrine Event Listener** (Entity Lifecycle)

**File:**
- `src/EventListener/ProductLifecycleListener.php`

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;

#[AsEntityListener(event: Events::prePersist, entity: Product::class)]
#[AsEntityListener(event: Events::preUpdate, entity: Product::class)]
#[AsEntityListener(event: Events::postPersist, entity: Product::class)]
class ProductLifecycleListener
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public function prePersist(Product $product): void
    {
        // Set timestamps
        if ($product->getCreatedAt() === null) {
            $product->setCreatedAt(new \DateTimeImmutable());
        }

        $this->logger->info('About to persist product', [
            'name' => $product->getName(),
        ]);
    }

    public function preUpdate(Product $product): void
    {
        $product->setUpdatedAt(new \DateTimeImmutable());

        $this->logger->info('About to update product', [
            'id' => $product->getId(),
        ]);
    }

    public function postPersist(Product $product): void
    {
        $this->logger->info('Product persisted', [
            'id' => $product->getId(),
        ]);
    }
}
```

### 4. **Kernel Event Subscriber** (Request/Response)

**File:**
- `src/EventSubscriber/RequestEventSubscriber.php`

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RequestEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10],
            KernelEvents::RESPONSE => 'onKernelResponse',
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        $this->logger->info('Request received', [
            'method' => $request->getMethod(),
            'path' => $request->getPathInfo(),
            'ip' => $request->getClientIp(),
        ]);

        // Add custom headers, modify request, etc.
        $request->headers->set('X-Request-Time', (string) time());
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();

        // Add custom headers
        $response->headers->set('X-Custom-Header', 'value');

        $this->logger->info('Response sent', [
            'status' => $response->getStatusCode(),
        ]);
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $this->logger->error('Exception thrown', [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);
    }
}
```

### 5. **Security Event Subscriber**

**File:**
- `src/EventSubscriber/SecurityEventSubscriber.php`

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class SecurityEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
            LoginFailureEvent::class => 'onLoginFailure',
            LogoutEvent::class => 'onLogout',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();

        $this->logger->info('User logged in', [
            'username' => $user->getUserIdentifier(),
            'ip' => $event->getRequest()->getClientIp(),
        ]);

        // Update last login timestamp, clear failed attempts, etc.
    }

    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $this->logger->warning('Login failed', [
            'ip' => $event->getRequest()->getClientIp(),
            'reason' => $event->getException()->getMessage(),
        ]);

        // Track failed attempts, implement rate limiting, etc.
    }

    public function onLogout(LogoutEvent $event): void
    {
        $user = $event->getToken()?->getUser();

        if ($user) {
            $this->logger->info('User logged out', [
                'username' => $user->getUserIdentifier(),
            ]);
        }
    }
}
```

## Common Event Patterns

### **Stoppable Event**
```php
<?php

declare(strict_types=1);

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

class OrderProcessingEvent extends Event
{
    private bool $shouldProcess = true;

    public function __construct(
        private readonly Order $order,
    ) {}

    public function getOrder(): Order
    {
        return $this->order;
    }

    public function preventProcessing(): void
    {
        $this->shouldProcess = false;
        $this->stopPropagation();
    }

    public function shouldProcess(): bool
    {
        return $this->shouldProcess;
    }
}
```

### **Event with Modification**
```php
<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Product;
use Symfony\Contracts\EventDispatcher\Event;

class ProductPriceCalculationEvent extends Event
{
    private float $finalPrice;

    public function __construct(
        private readonly Product $product,
        float $basePrice,
    ) {
        $this->finalPrice = $basePrice;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function getFinalPrice(): float
    {
        return $this->finalPrice;
    }

    public function setFinalPrice(float $price): void
    {
        $this->finalPrice = $price;
    }

    public function applyDiscount(float $percentage): void
    {
        $this->finalPrice *= (1 - $percentage / 100);
    }
}
```

## Event Priority

Higher priority = runs first (default is 0)

```php
public static function getSubscribedEvents(): array
{
    return [
        'app.event' => [
            ['highPriorityMethod', 100],  // Runs first
            ['normalPriorityMethod', 0],  // Runs second (default)
            ['lowPriorityMethod', -100],  // Runs last
        ],
    ];
}
```

## Testing Events

### **Test Event Dispatch**
```php
<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Event\UserRegisteredEvent;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class UserServiceTest extends KernelTestCase
{
    public function testUserRegistrationDispatchesEvent(): void
    {
        self::bootKernel();

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->isInstanceOf(UserRegisteredEvent::class),
                UserRegisteredEvent::NAME
            );

        $userService = new UserService($eventDispatcher);
        $userService->registerUser('test@example.com', 'password');
    }
}
```

### **Test Listener**
```php
<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\Event\UserRegisteredEvent;
use App\EventListener\UserRegisteredListener;
use PHPUnit\Framework\TestCase;

class UserRegisteredListenerTest extends TestCase
{
    public function testListenerSendsEmail(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())->method('send');

        $listener = new UserRegisteredListener($mailer, $this->logger);

        $user = new User();
        $user->setEmail('test@example.com');

        $event = new UserRegisteredEvent($user, new \DateTimeImmutable());
        $listener($event);
    }
}
```

## Configuration (services.yaml)

### **Explicit Listener Registration**
```yaml
services:
    App\EventListener\UserRegisteredListener:
        tags:
            - { name: kernel.event_listener, event: user.registered }
```

### **Multiple Events**
```yaml
services:
    App\EventListener\ProductListener:
        tags:
            - { name: kernel.event_listener, event: product.created, method: onCreate }
            - { name: kernel.event_listener, event: product.updated, method: onUpdate, priority: 10 }
```

### **Subscriber Auto-Registration**
```yaml
services:
    App\EventSubscriber\ProductEventSubscriber:
        # Auto-registered as subscriber
```

## Built-in Symfony Events

### **Kernel Events**
- `kernel.request` - Before controller execution
- `kernel.controller` - After controller is resolved
- `kernel.view` - When controller returns non-Response
- `kernel.response` - Before response is sent
- `kernel.finish_request` - After response is sent
- `kernel.exception` - When exception is thrown
- `kernel.terminate` - After response is sent to client

### **Security Events**
- `Symfony\Component\Security\Http\Event\LoginSuccessEvent`
- `Symfony\Component\Security\Http\Event\LoginFailureEvent`
- `Symfony\Component\Security\Http\Event\LogoutEvent`

### **Console Events**
- `console.command` - Before command execution
- `console.terminate` - After command execution
- `console.error` - When command throws exception

### **Doctrine Events**
- `prePersist` - Before entity is persisted
- `postPersist` - After entity is persisted
- `preUpdate` - Before entity is updated
- `postUpdate` - After entity is updated
- `preRemove` - Before entity is removed
- `postRemove` - After entity is removed
- `postLoad` - After entity is loaded

## Best Practices

**Event Design**
- Events should be immutable (readonly properties)
- Use clear, descriptive names (UserRegisteredEvent)
- Include relevant context data
- Use const NAME for event names

**Listener/Subscriber Choice**
- Use Listener for single event handling
- Use Subscriber for multiple related events
- Use Subscriber for complex event logic

**Performance**
- Keep listeners fast and simple
- Offload heavy tasks to Messenger
- Use priority to control execution order
- Avoid database queries in listeners

**Organization**
- Group related events in same namespace
- One event class per business event
- Keep listeners focused (single responsibility)
- Document event purpose and data

**Testing**
- Test event dispatch
- Test listener behavior
- Test event data
- Mock dependencies

Generate production-ready Symfony events with listeners and subscribers for robust event-driven architecture.

## Next Steps

After creating events, consider:
- `/test-new` — Write tests for event listeners and subscribers
- `/messenger-new` — Make listeners async via Messenger
