---
description: Add authentication, authorization, and security to API endpoints
model: claude-sonnet-4-6
effort: high
---

Add comprehensive security, authentication, and authorization to Symfony API endpoints.

## Target API Route

$ARGUMENTS

## Security Layers to Implement

### 1. **Authentication** (Who are you?)
- Verify user identity
- Token validation (JWT, API keys, session)
- Handle expired/invalid credentials
- Integration with Symfony Security component

### 2. **Authorization** (What can you do?)
- Role-based access control (RBAC)
- Security Voters for resource-level permissions
- Attribute-based access control (#[IsGranted])
- Check user ownership

### 3. **Input Validation**
- Symfony Validator constraints
- DTO validation with #[MapRequestPayload]
- SQL injection prevention (Doctrine ORM)
- XSS prevention (Twig auto-escaping)
- Type validation with strict PHP types

### 4. **Rate Limiting**
- Symfony Rate Limiter component
- Per-user/IP limits
- Token bucket or fixed window algorithm
- Graceful degradation

### 5. **CORS** (if needed)
- Configure nelmio/cors-bundle
- Whitelist allowed origins
- Proper headers and credentials
- Preflight request handling

## Symfony Security Architecture

### **Authentication Methods**

**1. JWT Authentication** (Recommended for APIs)
```bash
# Install LexikJWTAuthenticationBundle
composer require lexik/jwt-authentication-bundle

# Generate keys
ddev exec bin/console lexik:jwt:generate-keypair
```

**2. API Key Authentication**
```php
// Custom authenticator
namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

class ApiKeyAuthenticator extends AbstractAuthenticator
{
    public function supports(Request $request): ?bool
    {
        return $request->headers->has('X-API-KEY');
    }

    public function authenticate(Request $request): Passport
    {
        $apiKey = $request->headers->get('X-API-KEY');

        if (!$apiKey) {
            throw new AuthenticationException('No API key provided');
        }

        // Validate API key and create passport
        // ...
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null; // Allow request to continue
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'type' => 'authentication_error',
            'message' => 'Authentication failed',
        ], Response::HTTP_UNAUTHORIZED);
    }
}
```

**3. Session-Based Authentication** (For web + API)
```yaml
# config/packages/security.yaml
security:
    firewalls:
        api:
            pattern: ^/api
            stateless: true
            json_login:
                check_path: /api/login
                success_handler: lexik_jwt_authentication.handler.authentication_success
                failure_handler: lexik_jwt_authentication.handler.authentication_failure
            jwt: ~
```

## Implementation Patterns

### **Pattern 1: Simple Attribute-Based Protection**

```php
<?php

declare(strict_types=1);

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin')]
#[IsGranted('ROLE_ADMIN')] // Protects all routes in controller
class AdminController extends AbstractController
{
    #[Route('/users', name: 'api_admin_users', methods: ['GET'])]
    public function listUsers(): JsonResponse
    {
        // Only accessible to ROLE_ADMIN
        return $this->json(['users' => []]);
    }

    #[Route('/users/{id}', name: 'api_admin_delete_user', methods: ['DELETE'])]
    #[IsGranted('ROLE_SUPER_ADMIN')] // Additional role requirement
    public function deleteUser(int $id): JsonResponse
    {
        // Only accessible to ROLE_SUPER_ADMIN
        return $this->json(['message' => 'User deleted'], 204);
    }
}
```

### **Pattern 2: Security Voters for Resource Ownership**

**Voter** (`src/Security/ProductVoter.php`)
```php
<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Product;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ProductVoter extends Voter
{
    public const EDIT = 'PRODUCT_EDIT';
    public const DELETE = 'PRODUCT_DELETE';
    public const VIEW = 'PRODUCT_VIEW';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::DELETE, self::VIEW])
            && $subject instanceof Product;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Product $product */
        $product = $subject;

        return match($attribute) {
            self::VIEW => $this->canView($product, $user),
            self::EDIT => $this->canEdit($product, $user),
            self::DELETE => $this->canDelete($product, $user),
            default => false,
        };
    }

    private function canView(Product $product, User $user): bool
    {
        // Everyone can view published products
        if ($product->isPublished()) {
            return true;
        }

        // Owners can view their own unpublished products
        return $product->getOwner() === $user;
    }

    private function canEdit(Product $product, User $user): bool
    {
        // Only owner or admin can edit
        return $product->getOwner() === $user
            || in_array('ROLE_ADMIN', $user->getRoles());
    }

    private function canDelete(Product $product, User $user): bool
    {
        // Only owner can delete
        return $product->getOwner() === $user;
    }
}
```

**Controller Using Voter**
```php
<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Security\ProductVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/products')]
class ProductController extends AbstractController
{
    #[Route('/{id}', name: 'api_product_show', methods: ['GET'])]
    public function show(Product $product): JsonResponse
    {
        // Check if user can view this product
        $this->denyAccessUnlessGranted(ProductVoter::VIEW, $product);

        return $this->json(['data' => $product]);
    }

    #[Route('/{id}', name: 'api_product_update', methods: ['PUT'])]
    #[IsGranted(ProductVoter::EDIT, 'product')] // Using attribute
    public function update(Product $product): JsonResponse
    {
        // User is authorized to edit
        return $this->json(['data' => $product]);
    }

    #[Route('/{id}', name: 'api_product_delete', methods: ['DELETE'])]
    public function delete(Product $product): JsonResponse
    {
        // Check imperatively
        $this->denyAccessUnlessGranted(ProductVoter::DELETE, $product);

        // Delete logic...
        return $this->json([], 204);
    }
}
```

### **Pattern 3: Input Validation with DTOs**

**DTO with Validation** (`src/Dto/CreateProductRequest.php`)
```php
<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class CreateProductRequest
{
    #[Assert\NotBlank(message: 'Name is required')]
    #[Assert\Length(min: 3, max: 255)]
    public string $name;

    #[Assert\NotBlank]
    #[Assert\Positive]
    public float $price;

    #[Assert\Length(max: 1000)]
    public ?string $description = null;

    #[Assert\Choice(choices: ['active', 'inactive', 'draft'])]
    public string $status = 'draft';
}
```

**Controller with DTO Validation**
```php
<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Dto\CreateProductRequest;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/products')]
class ProductController extends AbstractController
{
    #[Route('', name: 'api_product_create', methods: ['POST'])]
    public function create(
        #[MapRequestPayload] CreateProductRequest $request,
        EntityManagerInterface $em
    ): JsonResponse {
        // DTO is automatically validated
        // If validation fails, returns 400 with errors

        $product = new Product();
        $product->setName($request->name);
        $product->setPrice($request->price);
        $product->setDescription($request->description);
        $product->setStatus($request->status);

        $em->persist($product);
        $em->flush();

        return $this->json([
            'data' => $product,
            'status' => 'success',
        ], Response::HTTP_CREATED);
    }
}
```

### **Pattern 4: Rate Limiting**

**Configuration** (`config/packages/rate_limiter.yaml`)
```yaml
framework:
    rate_limiter:
        api_anonymous:
            policy: 'sliding_window'
            limit: 100
            interval: '60 minutes'

        api_authenticated:
            policy: 'token_bucket'
            limit: 1000
            rate:
                interval: '60 minutes'
                amount: 1000

        api_login:
            policy: 'fixed_window'
            limit: 5
            interval: '15 minutes'
```

**Controller with Rate Limiting**
```php
<?php

declare(strict_types=1);

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

class ProductController extends AbstractController
{
    #[Route('/api/products', name: 'api_products_list', methods: ['GET'])]
    public function list(
        Request $request,
        RateLimiterFactory $apiAnonymousLimiter
    ): JsonResponse {
        // Apply rate limiting
        $limiter = $apiAnonymousLimiter->create($request->getClientIp());

        if (!$limiter->consume(1)->isAccepted()) {
            throw new TooManyRequestsHttpException(
                retryAfter: $limiter->consume(1)->getRetryAfter()->getTimestamp()
            );
        }

        return $this->json(['data' => []]);
    }
}
```

### **Pattern 5: CORS Configuration**

```bash
# Install nelmio/cors-bundle
composer require nelmio/cors-bundle
```

**Configuration** (`config/packages/nelmio_cors.yaml`)
```yaml
nelmio_cors:
    defaults:
        origin_regex: true
        allow_origin: ['%env(CORS_ALLOW_ORIGIN)%']
        allow_methods: ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS']
        allow_headers: ['Content-Type', 'Authorization', 'X-Requested-With']
        expose_headers: ['Link', 'X-Total-Count']
        max_age: 3600

    paths:
        '^/api/':
            allow_origin: ['https://app.example.com', 'https://admin.example.com']
            allow_headers: ['*']
            allow_methods: ['POST', 'PUT', 'GET', 'DELETE', 'PATCH']
            max_age: 3600
```

## Security Configuration

### **security.yaml** (Complete Example)

```yaml
security:
    # Password hashing
    password_hashers:
        Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface: 'auto'

    # User providers
    providers:
        app_user_provider:
            entity:
                class: App\Entity\User
                property: email

    # Firewalls
    firewalls:
        dev:
            pattern: ^/(_(profiler|wdt)|css|images|js)/
            security: false

        login:
            pattern: ^/api/login
            stateless: true
            json_login:
                check_path: /api/login
                success_handler: lexik_jwt_authentication.handler.authentication_success
                failure_handler: lexik_jwt_authentication.handler.authentication_failure

        api:
            pattern: ^/api
            stateless: true
            jwt: ~

        main:
            lazy: true
            provider: app_user_provider

    # Access control
    access_control:
        - { path: ^/api/login, roles: PUBLIC_ACCESS }
        - { path: ^/api/register, roles: PUBLIC_ACCESS }
        - { path: ^/api/admin, roles: ROLE_ADMIN }
        - { path: ^/api, roles: IS_AUTHENTICATED }

    # Role hierarchy
    role_hierarchy:
        ROLE_ADMIN: [ROLE_USER]
        ROLE_SUPER_ADMIN: [ROLE_ADMIN, ROLE_ALLOWED_TO_SWITCH]
```

## Error Handling

### **Custom Exception Listener**

```php
<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class ExceptionListener
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        // Only handle API requests
        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        $statusCode = $exception instanceof HttpExceptionInterface
            ? $exception->getStatusCode()
            : Response::HTTP_INTERNAL_SERVER_ERROR;

        $response = match (true) {
            $exception instanceof AuthenticationException => new JsonResponse([
                'type' => 'authentication_error',
                'title' => 'Authentication Failed',
                'status' => 401,
                'detail' => 'Invalid or missing authentication credentials',
            ], 401),

            $exception instanceof AccessDeniedHttpException => new JsonResponse([
                'type' => 'authorization_error',
                'title' => 'Access Denied',
                'status' => 403,
                'detail' => 'You do not have permission to access this resource',
            ], 403),

            default => new JsonResponse([
                'type' => 'error',
                'title' => 'An error occurred',
                'status' => $statusCode,
                'detail' => $exception->getMessage(),
            ], $statusCode),
        };

        $event->setResponse($response);
    }
}
```

## Testing Security

### **Test Authentication**

```php
<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SecurityTest extends WebTestCase
{
    public function testProtectedEndpointRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/products');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testAuthenticatedUserCanAccessProtectedEndpoint(): void
    {
        $client = static::createClient();

        // Create and login user
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneByEmail('test@example.com');
        $client->loginUser($user);

        $client->request('GET', '/api/products');

        $this->assertResponseIsSuccessful();
    }

    public function testNonAdminCannotAccessAdminEndpoint(): void
    {
        $client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneByEmail('user@example.com');
        $client->loginUser($user);

        $client->request('GET', '/api/admin/users');

        $this->assertResponseStatusCodeSame(403);
    }
}
```

## DDEV Integration

```bash
# Generate JWT keys
ddev exec bin/console lexik:jwt:generate-keypair

# Create user
ddev exec bin/console make:user

# Test protected endpoint
ddev exec curl -H "Authorization: Bearer YOUR_JWT_TOKEN" https://myproject.ddev.site/api/products

# Run security tests
ddev exec bin/phpunit tests/Controller/Api/SecurityTest.php
```

## Security Checklist

**Authentication**
- ✅ JWT or API key authentication configured
- ✅ Token expiration handled (401)
- ✅ Invalid tokens rejected (401)
- ✅ Secure token storage in .env (JWT keys)
- ✅ HTTPS enforced in production

**Authorization**
- ✅ Role-based access control configured
- ✅ Security voters for resource permissions
- ✅ #[IsGranted] attributes used
- ✅ Authorization failures return 403
- ✅ Log authorization failures

**Input Validation**
- ✅ Symfony Validator constraints on all DTOs
- ✅ Type-safe with strict PHP types
- ✅ Doctrine ORM prevents SQL injection
- ✅ Twig auto-escapes output (XSS prevention)
- ✅ Payload size limits configured

**Rate Limiting**
- ✅ Rate limiter configured per endpoint
- ✅ Different limits for authenticated/anonymous
- ✅ Login attempts rate limited
- ✅ Returns 429 with Retry-After header

**CORS**
- ✅ CORS bundle configured
- ✅ Allowed origins whitelisted
- ✅ Credentials handling configured
- ✅ Preflight requests handled

**Error Handling**
- ✅ Custom exception listener for APIs
- ✅ No stack traces in production
- ✅ Consistent error format (Problem Details RFC 7807)
- ✅ Detailed errors logged server-side

**Logging & Monitoring**
- ✅ Authentication attempts logged
- ✅ Authorization failures logged
- ✅ Suspicious activity tracked
- ✅ Rate limit violations monitored

Generate production-ready, secure Symfony API endpoints following OWASP best practices and the principle of least privilege.

## Next Steps

After securing an API, consider:
- `/api-test` — Update tests to include authentication and authorization scenarios
- `/voter-new` — Create fine-grained authorization voters
