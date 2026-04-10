---
description: Extract services from controllers, apply SOLID, modernize to PHP 8.2+ patterns
model: claude-sonnet-4-6
effort: medium
---

Clean up and refactor the following PHP/Symfony code to improve readability, maintainability, and follow best practices.

## Code to Clean

$ARGUMENTS

## Cleanup Checklist for Symfony Developers

### 1. **Code Smells to Fix**

**Naming**
- ✅ Descriptive variable/function names
- ✅ PSR-12 naming conventions (camelCase methods, PascalCase classes)
- ✅ Avoid abbreviations unless obvious
- ✅ Boolean names start with is/has/can

**Functions/Methods**
- ✅ Single responsibility per method
- ✅ Keep methods small (<50 lines)
- ✅ Reduce parameters (max 3-4, use DTOs for more)
- ✅ Extract complex logic to services
- ✅ Avoid side effects where possible

**DRY (Don't Repeat Yourself)**
- ✅ Extract repeated code to services/utilities
- ✅ Create reusable Twig components
- ✅ Use traits for shared behavior
- ✅ Centralize constants in parameters.yaml

**Complexity**
- ✅ Reduce nested if statements
- ✅ Replace complex conditions with methods
- ✅ Use early returns/guard clauses
- ✅ Simplify boolean logic

**Type Safety**
- ✅ Remove mixed types
- ✅ Add strict types (`declare(strict_types=1)`)
- ✅ Use proper type hints for all parameters/returns
- ✅ Leverage PHP 8.2+ features (readonly, enums)

### 2. **Modern Patterns to Apply**

**PHP 8.2+**
```php
// Use null-safe operator
$value = $obj?->prop?->nested;

// Use nullish coalescing
$result = $value ?? 'default';

// Use match expressions
$result = match($status) {
    'active' => 'Active User',
    'inactive' => 'Inactive User',
    default => 'Unknown',
};

// Use named arguments
createUser(
    email: 'test@example.com',
    name: 'John Doe',
    active: true
);

// Use readonly properties
readonly class UserDto {
    public function __construct(
        public string $email,
        public string $name,
    ) {}
}
```

**Symfony**
```php
// Use constructor property promotion
public function __construct(
    private readonly UserRepository $userRepository,
    private readonly EntityManagerInterface $em,
) {}

// Use attributes instead of annotations
#[Route('/users', name: 'users_index')]
#[IsGranted('ROLE_USER')]
public function index(): Response

// Use DTO with validation
#[Route('/users', methods: ['POST'])]
public function create(
    #[MapRequestPayload] CreateUserRequest $request
): JsonResponse
```

### 3. **Refactoring Techniques**

**Extract Method**
```php
// Before: Fat controller
class UserController extends AbstractController
{
    #[Route('/users', methods: ['POST'])]
    public function create(Request $request): Response
    {
        // 50 lines of validation, transformation, persistence
    }
}

// After: Thin controller with service
class UserController extends AbstractController
{
    #[Route('/users', methods: ['POST'])]
    public function create(
        #[MapRequestPayload] CreateUserRequest $request,
        UserService $userService
    ): Response {
        $user = $userService->createUser($request);

        return $this->json($user, Response::HTTP_CREATED);
    }
}

class UserService
{
    public function createUser(CreateUserRequest $request): User
    {
        $this->validate($request);
        $user = $this->transform($request);
        $this->persist($user);

        return $user;
    }

    private function validate(CreateUserRequest $request): void { }
    private function transform(CreateUserRequest $request): User { }
    private function persist(User $user): void { }
}
```

**Replace Conditional with Strategy Pattern**
```php
// Before: Long if/else chains
public function process(string $type, array $data): mixed
{
    if ($type === 'email') {
        return $this->processEmail($data);
    } elseif ($type === 'sms') {
        return $this->processSms($data);
    } elseif ($type === 'push') {
        return $this->processPush($data);
    }
}

// After: Strategy pattern
interface NotificationProcessorInterface
{
    public function process(array $data): mixed;
}

class NotificationService
{
    public function __construct(
        private readonly iterable $processors // Tagged services
    ) {}

    public function process(string $type, array $data): mixed
    {
        foreach ($this->processors as $processor) {
            if ($processor->supports($type)) {
                return $processor->process($data);
            }
        }

        throw new \InvalidArgumentException("No processor for type: $type");
    }
}
```

**Introduce DTO/Value Object**
```php
// Before: Many parameters
public function createUser(
    string $email,
    string $name,
    int $age,
    string $address,
    string $phone
): User

// After: DTO
readonly class CreateUserRequest
{
    public function __construct(
        public string $email,
        public string $name,
        public int $age,
        public string $address,
        public string $phone,
    ) {}
}

public function createUser(CreateUserRequest $request): User
```

### 4. **Common Cleanup Tasks**

**Remove Dead Code**
- Unused imports (use statements)
- Unreachable code after return
- Commented out code
- Unused variables and methods

**Improve Error Handling**
```php
// Before
try {
    $this->doSomething();
} catch (\Exception $e) {
    // Silent failure or var_dump
}

// After
try {
    $this->doSomething();
} catch (ValidationException $e) {
    return $this->json([
        'error' => 'Validation failed',
        'violations' => $e->getViolations(),
    ], Response::HTTP_BAD_REQUEST);
} catch (\Exception $e) {
    $this->logger->error('Unexpected error', [
        'exception' => $e,
        'trace' => $e->getTraceAsString(),
    ]);

    throw $e;
}
```

**Consistent Formatting**
- Use PHP CS Fixer
- PSR-12 standard
- Line length (<120 characters)
- Organized imports

**Better Comments**
```php
// ❌ Remove obvious comments
// Set the name
$user->setName($name);

// ✅ Add why, not what
// Cache user preferences for 1 hour to reduce database load
$preferences = $this->cache->get('user_prefs_' . $userId, function() {
    return $this->userRepository->getPreferences($userId);
});

// ✅ Document complex logic
// Use exponential backoff: 1s, 2s, 4s, 8s, 16s
for ($i = 0; $i < 5; $i++) {
    try {
        return $this->apiCall();
    } catch (ApiException $e) {
        sleep(2 ** $i);
    }
}
```

### 5. **Symfony Specific Cleanup**

**Controller Cleanup**
```php
// Before: Messy controller
class ProductController extends AbstractController
{
    #[Route('/products/{id}')]
    public function show($id)
    {
        $em = $this->getDoctrine()->getManager();
        $product = $em->getRepository(Product::class)->find($id);

        if (!$product) {
            return new Response('Not found', 404);
        }

        return $this->render('products/show.html.twig', [
            'product' => $product
        ]);
    }
}

// After: Clean controller
class ProductController extends AbstractController
{
    #[Route('/products/{id}', name: 'products_show', requirements: ['id' => '\d+'])]
    public function show(Product $product): Response
    {
        // ParamConverter automatically finds product or throws 404
        return $this->render('products/show.html.twig', [
            'product' => $product,
        ]);
    }
}
```

**Repository Cleanup**
```php
// Before: Query in controller
$users = $em->createQueryBuilder()
    ->select('u')
    ->from(User::class, 'u')
    ->where('u.active = :active')
    ->setParameter('active', true)
    ->getQuery()
    ->getResult();

// After: Custom repository method
class UserRepository extends ServiceEntityRepository
{
    public function findActiveUsers(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.active = :active')
            ->setParameter('active', true)
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
```

**Service Configuration**
```php
// Before: Manual service instantiation
$service = new UserService($em, $validator, $logger);

// After: Dependency injection
public function __construct(
    private readonly UserService $userService
) {}
```

### 6. **DDEV Integration**

**Run Code Quality Tools**
```bash
# PHP CS Fixer (auto-fix code style)
ddev exec vendor/bin/php-cs-fixer fix src/

# PHPStan (static analysis)
ddev exec vendor/bin/phpstan analyse src tests

# Rector (automated refactoring)
ddev exec vendor/bin/rector process src/

# Symfony linter
ddev exec bin/console lint:twig templates/
ddev exec bin/console lint:yaml config/
ddev exec bin/console lint:container
```

## Output Format

1. **Issues Found** - List of code smells and problems
2. **Cleaned Code** - Refactored version with strict types and modern PHP
3. **Explanations** - What changed and why (patterns applied)
4. **Before/After Comparison** - Side-by-side if helpful
5. **Further Improvements** - Optional enhancements (services, repositories, DTOs)
6. **Quality Commands** - DDEV commands to maintain code quality

Focus on practical improvements that make code more maintainable without over-engineering. Balance clean code with pragmatism for solo development.

## Next Steps

After cleaning up code, consider:
- `/lint` — Run PHPStan and PHP CS Fixer to verify quality
- `/test-new` — Add tests for refactored code
