---
description: Create a new Symfony API endpoint with validation, error handling, and type safety
model: claude-sonnet-4-6
effort: medium
argument-hint: "[describe endpoint, e.g. 'CRUD API for blog posts with pagination']"
---

Create a new Symfony API endpoint following modern best practices for solo developers.

## Requirements

API Endpoint: $ARGUMENTS

## Implementation Guidelines

### 1. **Symfony 7+ Controllers**
Use Controllers in `src/Controller/` directory with proper routing and type hints

### 2. **Validation**
- Use Symfony Validator component with Constraints
- Validate DTOs/Entities before processing
- Return clear validation error messages with Problem Details (RFC 7807)

### 3. **Error Handling**
- Use custom exception handlers or Event Subscribers
- Consistent error response format (JSON API or Problem Details)
- Appropriate HTTP status codes
- Never expose sensitive error details in production

### 4. **Type Safety**
- Strict PHP 8.2+ types for all methods
- Use DTOs for request/response data
- Type-hint everything (no mixed types unless necessary)
- Enable strict_types=1

### 5. **Security**
- Input sanitization via Symfony Validator
- CORS configuration in config/packages/nelmio_cors.yaml
- Rate limiting with symfony/rate-limiter
- Authentication/authorization with Security voters

### 6. **Response Format**
```php
// Success (200/201)
{
    "data": { /* resource data */ },
    "status": "success"
}

// Error (4xx/5xx)
{
    "type": "https://example.com/errors/validation",
    "title": "Validation Failed",
    "status": 400,
    "detail": "Invalid input data",
    "violations": [
        {"field": "email", "message": "This value is not a valid email"}
    ]
}
```

## Code Structure

Create a complete API endpoint with:

1. **Controller** - `src/Controller/Api/[ResourceName]Controller.php`
2. **Entity** - `src/Entity/[ResourceName].php` (if CRUD)
3. **DTO** - `src/Dto/[ResourceName]Request.php` and `Response.php`
4. **Repository** - `src/Repository/[ResourceName]Repository.php`
5. **Validator Constraints** - Custom constraints if needed
6. **Routing** - Attributes on controller methods
7. **Tests** - `tests/Controller/Api/[ResourceName]ControllerTest.php`

## Best Practices to Follow

### Architecture
- Use API Platform for REST/GraphQL APIs when appropriate
- Follow RESTful conventions (GET, POST, PUT, PATCH, DELETE)
- Use HTTP method override for older clients if needed
- Implement HATEOAS links for resource discoverability

### Code Quality
- Early validation before expensive operations (DB queries)
- Proper HTTP status codes:
  - 200 OK (successful GET/PUT/PATCH)
  - 201 Created (successful POST)
  - 204 No Content (successful DELETE)
  - 400 Bad Request (validation errors)
  - 401 Unauthorized (missing/invalid auth)
  - 403 Forbidden (insufficient permissions)
  - 404 Not Found (resource doesn't exist)
  - 422 Unprocessable Entity (semantic errors)
  - 500 Internal Server Error (unexpected errors)
- Consistent error response format
- Minimal logic in controllers (use services)
- Environment variable validation with symfony/dotenv
- Request/response logging for debugging (Monolog)
- Never expose sensitive data in responses
- Never execute DB queries without validation
- Extract business logic to services

### Symfony-Specific
- Use dependency injection for all services
- Leverage Symfony Serializer for JSON transformation
- Use ParamConverter for automatic entity hydration
- Implement versioning (URL versioning or Accept header)
- Add OpenAPI/Swagger documentation with nelmio/api-doc-bundle
- Use Symfony Messenger for async tasks
- Configure proper Content-Type negotiation

### DDEV Integration
- Ensure API works inside DDEV container
- Use `ddev exec` for running tests
- Configure xdebug for API debugging if needed
- Use DDEV's mailhog for email testing

## Example Symfony Controller Structure

```php
<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Dto\CreateUserRequest;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/users', name: 'api_users_')]
class UserController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $userRepository,
        private readonly ValidatorInterface $validator,
    ) {}

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        #[MapRequestPayload] CreateUserRequest $request
    ): JsonResponse {
        $user = new User();
        $user->setEmail($request->email);
        $user->setName($request->name);

        $this->em->persist($user);
        $this->em->flush();

        return $this->json([
            'data' => $user,
            'status' => 'success',
        ], Response::HTTP_CREATED);
    }
}
```

Generate production-ready Symfony code that I can immediately use in my project running on DDEV.

## Next Steps

After creating an API endpoint, consider:
- `/api-protect` — Add authentication, authorization, and rate limiting
- `/api-test` — Generate functional tests for this endpoint
- `/dto-new` — Create request/response DTOs with validation
