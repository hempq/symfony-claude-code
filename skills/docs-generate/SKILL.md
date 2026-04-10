---
description: Generate documentation for code, APIs, and components
model: claude-sonnet-4-6
effort: medium
---

Generate comprehensive documentation for the following Symfony/PHP code.

## Code to Document

$ARGUMENTS

## Documentation Strategy for Symfony Developers

### 1. **Documentation Types**

**Code Documentation**
- PHPDoc comments
- Method/function descriptions
- Parameter types and descriptions
- Return types and exceptions
- Usage examples

**API Documentation**
- Endpoint descriptions
- Request/response formats
- Authentication requirements
- HTTP status codes
- Examples with curl

**Component Documentation**
- Twig component props
- Usage examples
- LiveComponent actions
- Accessibility notes

**README Documentation**
- Project overview
- Setup instructions
- Environment variables
- DDEV commands
- Deployment guide

### 2. **PHPDoc Format**

```php
<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Exception\UserNotFoundException;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Manages user operations including creation, updates, and retrieval.
 *
 * This service handles all business logic related to user management,
 * including validation, persistence, and notification.
 */
class UserService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $userRepository,
    ) {}

    /**
     * Fetches a user by their unique identifier.
     *
     * @param int $userId The unique identifier for the user
     * @param bool $includeProfile Whether to eager load the user's profile
     *
     * @return User The user entity
     *
     * @throws UserNotFoundException When the user doesn't exist
     *
     * @example
     * ```php
     * $user = $userService->getUser(123, includeProfile: true);
     * echo $user->getEmail();
     * ```
     */
    public function getUser(int $userId, bool $includeProfile = false): User
    {
        $user = $includeProfile
            ? $this->userRepository->findWithProfile($userId)
            : $this->userRepository->find($userId);

        if (!$user) {
            throw new UserNotFoundException("User with ID {$userId} not found");
        }

        return $user;
    }
}
```

### 3. **API Documentation**

```markdown
## POST /api/users

Create a new user account.

### Authentication
Requires valid JWT token in `Authorization` header.

**Required Role:** `ROLE_ADMIN`

### Request Body

```json
{
  "email": "user@example.com",
  "name": "John Doe",
  "role": "ROLE_USER"
}
```

**Fields:**
- `email` (string, required): Valid email address
- `name` (string, required): User's full name (2-255 characters)
- `role` (string, optional): User role (default: `ROLE_USER`)

### Response (201 Created)

```json
{
  "data": {
    "id": 123,
    "email": "user@example.com",
    "name": "John Doe",
    "role": "ROLE_USER",
    "createdAt": "2025-01-30T12:00:00+00:00"
  },
  "status": "success"
}
```

### Error Responses

**400 Bad Request** - Validation failed
```json
{
  "type": "validation_error",
  "title": "Validation Failed",
  "status": 400,
  "violations": [
    {
      "field": "email",
      "message": "This value is not a valid email address."
    }
  ]
}
```

**401 Unauthorized** - Missing or invalid authentication
```json
{
  "type": "authentication_error",
  "title": "Authentication Failed",
  "status": 401,
  "detail": "Invalid or missing authentication credentials"
}
```

**403 Forbidden** - Insufficient permissions
```json
{
  "type": "authorization_error",
  "title": "Access Denied",
  "status": 403,
  "detail": "You do not have permission to create users"
}
```

**409 Conflict** - Email already exists
```json
{
  "error": "User with this email already exists",
  "status": 409
}
```

### Example

**Using curl:**
```bash
curl -X POST https://api.example.com/api/users \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "name": "John Doe",
    "role": "ROLE_USER"
  }'
```

**Using Symfony HttpClient:**
```php
$response = $client->request('POST', '/api/users', [
    'headers' => [
        'Authorization' => 'Bearer ' . $token,
    ],
    'json' => [
        'email' => 'user@example.com',
        'name' => 'John Doe',
    ],
]);

$data = $response->toArray();
```
```

### 4. **Twig Component Documentation**

```php
<?php

declare(strict_types=1);

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * UserCard Component
 *
 * Displays user information in a card layout with avatar,
 * name, email, and optional action buttons.
 *
 * @example Basic usage
 * ```twig
 * {{ component('user_card', {
 *     user: user,
 *     showActions: true
 * }) }}
 * ```
 *
 * @example With custom CSS classes
 * ```twig
 * <twig:UserCard
 *     :user="user"
 *     showActions
 *     class="shadow-lg"
 * />
 * ```
 */
#[AsTwigComponent('user_card')]
class UserCard
{
    /**
     * The user entity to display.
     *
     * @var User
     */
    public User $user;

    /**
     * Whether to show action buttons (edit, delete).
     *
     * @var bool
     */
    public bool $showActions = false;

    /**
     * Size variant for the card.
     *
     * @var string One of: 'small', 'medium', 'large'
     */
    public string $size = 'medium';

    /**
     * Generates the avatar URL for the user.
     *
     * @return string The avatar URL or gravatar fallback
     */
    public function getAvatarUrl(): string
    {
        return $this->user->getAvatarUrl()
            ?? 'https://www.gravatar.com/avatar/' . md5($this->user->getEmail());
    }
}
```

### 5. **README Template for Symfony Projects**

```markdown
# Project Name

Brief description of your Symfony application.

## Features

- ✅ User authentication (JWT)
- ✅ RESTful API with Symfony
- ✅ Symfony UX components
- ✅ Doctrine ORM with PostgreSQL
- ✅ DDEV development environment

## Tech Stack

- **PHP** 8.2+
- **Symfony** 7.0+
- **Doctrine ORM** 3.0+
- **PostgreSQL** 15+
- **Symfony UX** (Twig Components, Live Components, Stimulus)
- **DDEV** for local development

## Getting Started

### Prerequisites

- DDEV installed ([installation guide](https://ddev.readthedocs.io/))
- Docker Desktop running
- Composer (optional, DDEV includes it)

### Installation

```bash
# Clone the repository
git clone https://github.com/username/project.git
cd project

# Start DDEV
ddev start

# Install dependencies
ddev composer install

# Set up environment
cp .env .env.local
# Edit .env.local with your configuration

# Create database
ddev exec bin/console doctrine:database:create

# Run migrations
ddev exec bin/console doctrine:migrations:migrate

# Load fixtures (optional)
ddev exec bin/console doctrine:fixtures:load
```

### Environment Variables

```bash
# Database
DATABASE_URL="postgresql://db:db@db:5432/db?serverVersion=15&charset=utf8"

# JWT Authentication
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your_passphrase_here

# Mailer
MAILER_DSN=smtp://localhost:1025

# App Configuration
APP_ENV=dev
APP_SECRET=generate_with_bin/console_secrets:generate-keys
```

### Development

```bash
# Start development server
ddev start

# Access the application
ddev launch

# Watch assets (if using Webpack Encore)
ddev exec npm run watch

# Run tests
ddev exec bin/phpunit

# Run code quality tools
ddev exec vendor/bin/php-cs-fixer fix
ddev exec vendor/bin/phpstan analyse

# View logs
ddev logs -f
```

## Project Structure

```
src/
├── Controller/         # HTTP controllers
├── Entity/            # Doctrine entities
├── Repository/        # Doctrine repositories
├── Service/           # Business logic services
├── Dto/               # Data Transfer Objects
├── Form/              # Symfony forms
├── Security/          # Security voters and authenticators
├── Twig/              # Twig extensions and components
│   └── Components/    # Symfony UX components
└── MessageHandler/    # Symfony Messenger handlers

templates/             # Twig templates
assets/                # Frontend assets (JS, CSS)
config/                # Configuration files
migrations/            # Database migrations
tests/                 # PHPUnit tests
```

## API Documentation

API documentation is available at `/api/doc` when running in dev mode.

Or view the [API documentation](docs/API.md).

## Testing

```bash
# Run all tests
ddev exec bin/phpunit

# Run specific test file
ddev exec bin/phpunit tests/Controller/UserControllerTest.php

# Run with coverage
ddev exec bin/phpunit --coverage-html var/coverage

# Access test database
ddev exec bin/console --env=test doctrine:database:create
```

## Deployment

### Building for Production

```bash
# Install production dependencies
composer install --no-dev --optimize-autoloader

# Build assets
npm run build

# Warm cache
bin/console cache:warmup --env=prod

# Run migrations
bin/console doctrine:migrations:migrate --no-interaction
```

### Environment Configuration

Ensure these environment variables are set in production:

- `APP_ENV=prod`
- `APP_DEBUG=0`
- `DATABASE_URL` (production database)
- `JWT_SECRET_KEY` and `JWT_PUBLIC_KEY`
- `MAILER_DSN` (production mail server)

## License

MIT

## Author

Your Name - [email protected]
```

### 6. **Inline Documentation Best Practices**

**Good Comments (Explain Why)**
```php
// Use aggressive caching here because product data rarely changes
// and this endpoint receives 10k+ requests/hour
$products = $this->cache->get('popular_products', function() {
    return $this->productRepository->findPopular();
}, ttl: 3600);

// Retry with exponential backoff: 1s, 2s, 4s, 8s
// This API is flaky but eventually succeeds
for ($i = 0; $i < 4; $i++) {
    try {
        return $this->externalApi->call();
    } catch (ApiException $e) {
        sleep(2 ** $i);
    }
}
```

**Bad Comments (State the Obvious)**
```php
// ❌ Don't do this
// Set the name to John
$user->setName('John');

// ❌ Or this
// Loop through products
foreach ($products as $product) { }
```

### 7. **Auto-Generated Docs**

**phpDocumentor**
```bash
# Install
ddev composer require --dev phpdocumentor/phpdocumentor

# Generate docs
ddev exec vendor/bin/phpdoc -d src -t docs/api
```

**API Platform (Auto API Docs)**
```bash
# Install API Platform
ddev composer require api

# Access docs at /api
# OpenAPI/Swagger UI automatically generated
```

**Symfony MakerBundle**
```bash
# Generate documentation stubs
ddev exec bin/console make:entity --with-docs
```

## DDEV Documentation Commands

```bash
# Generate API documentation
ddev exec bin/console nelmio:apidoc:dump > docs/api.json

# Lint documentation
ddev exec bin/console lint:twig templates/
ddev exec bin/console lint:yaml config/

# Generate PHPDoc
ddev exec vendor/bin/phpdoc

# View Symfony routes (for API docs)
ddev exec bin/console debug:router
```

## What to Generate

1. **PHPDoc Comments** - For all public methods and classes
2. **README Section** - Project setup and usage
3. **API Docs** - For all API endpoints with examples
4. **Component Props** - For Twig/Live Components with usage
5. **Usage Examples** - Real-world code snippets
6. **Configuration Guide** - Environment variables and setup
7. **Troubleshooting** - Common issues and solutions

Focus on documentation that helps developers understand and use the code quickly. Document the "why" not the "what".
