---
description: Create request/response DTOs with Symfony validation constraints and serialization
model: claude-sonnet-4-6
effort: high
context: fork
---

Create a Data Transfer Object (DTO) with validation following best practices.

## DTO Specification

$ARGUMENTS

## Existing Project Context

Existing DTOs:
!`find src/Dto src/DTO -name "*.php" -type f 2>/dev/null | sort`

Before generating, check existing DTOs for naming conventions and validation patterns.

## What to Generate

DTOs are used to transfer data between layers without exposing entities.

### 1. **API Request DTO** (Input Validation)

**File:**
- `src/Dto/CreateProductRequest.php`

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

readonly class CreateProductRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Name is required')]
        #[Assert\Length(min: 3, max: 255)]
        public string $name,

        #[Assert\NotBlank]
        #[Assert\Positive]
        #[Assert\Range(min: 0.01, max: 999999.99)]
        public float $price,

        #[Assert\Length(max: 5000)]
        public ?string $description = null,

        #[Assert\Choice(choices: ['active', 'inactive', 'draft'])]
        public string $status = 'draft',

        #[Assert\Valid]
        public ?array $tags = [],
    ) {}
}
```

**Controller Usage:**
```php
<?php

namespace App\Controller;

use App\Dto\CreateProductRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/products')]
class ProductApiController extends AbstractController
{
    #[Route('', methods: ['POST'])]
    public function create(
        #[MapRequestPayload] CreateProductRequest $request,
    ): JsonResponse {
        // Validation happens automatically via MapRequestPayload

        $product = new Product();
        $product->setName($request->name);
        $product->setPrice($request->price);
        $product->setDescription($request->description);
        $product->setStatus($request->status);

        // ... persist product

        return $this->json($product, Response::HTTP_CREATED);
    }
}
```

### 2. **API Response DTO** (Output Transformation)

**File:**
- `src/Dto/ProductResponse.php`

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Product;

readonly class ProductResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public float $price,
        public ?string $description,
        public string $status,
        public string $createdAt,
    ) {}

    public static function fromEntity(Product $product): self
    {
        return new self(
            id: $product->getId(),
            name: $product->getName(),
            price: $product->getPrice(),
            description: $product->getDescription(),
            status: $product->getStatus(),
            createdAt: $product->getCreatedAt()->format('Y-m-d H:i:s'),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
            'description' => $this->description,
            'status' => $this->status,
            'created_at' => $this->createdAt,
        ];
    }
}
```

**Controller Usage:**
```php
#[Route('/{id}', methods: ['GET'])]
public function show(Product $product): JsonResponse
{
    $response = ProductResponse::fromEntity($product);

    return $this->json($response);
}

#[Route('', methods: ['GET'])]
public function list(ProductRepository $repository): JsonResponse
{
    $products = $repository->findAll();

    $response = array_map(
        fn(Product $product) => ProductResponse::fromEntity($product),
        $products
    );

    return $this->json($response);
}
```

### 3. **Form DTO** (Without Entity)

**File:**
- `src/Dto/ContactFormData.php`

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class ContactFormData
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]
    public string $name = '';

    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email = '';

    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    #[Assert\Regex(pattern: '/^\+?[0-9\s\-()]+$/')]
    public string $phone = '';

    #[Assert\NotBlank]
    #[Assert\Length(min: 10, max: 1000)]
    public string $message = '';

    #[Assert\Choice(choices: ['general', 'support', 'sales'])]
    public string $subject = 'general';

    #[Assert\IsTrue(message: 'You must agree to the terms')]
    public bool $agreeToTerms = false;
}
```

**Form Type:**
```php
<?php

namespace App\Form;

use App\Dto\ContactFormData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->add('email', EmailType::class)
            ->add('phone', TextType::class)
            ->add('message', TextareaType::class)
            ->add('subject', ChoiceType::class, [
                'choices' => [
                    'General Inquiry' => 'general',
                    'Support' => 'support',
                    'Sales' => 'sales',
                ],
            ])
            ->add('agreeToTerms', CheckboxType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContactFormData::class,
        ]);
    }
}
```

### 4. **Update DTO** (Partial Updates)

**File:**
- `src/Dto/UpdateProductRequest.php`

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

readonly class UpdateProductRequest
{
    public function __construct(
        #[Assert\Length(min: 3, max: 255)]
        public ?string $name = null,

        #[Assert\Positive]
        public ?float $price = null,

        #[Assert\Length(max: 5000)]
        public ?string $description = null,

        #[Assert\Choice(choices: ['active', 'inactive', 'draft'])]
        public ?string $status = null,
    ) {}

    public function applyTo(Product $product): void
    {
        if ($this->name !== null) {
            $product->setName($this->name);
        }

        if ($this->price !== null) {
            $product->setPrice($this->price);
        }

        if ($this->description !== null) {
            $product->setDescription($this->description);
        }

        if ($this->status !== null) {
            $product->setStatus($this->status);
        }
    }
}
```

**Controller Usage:**
```php
#[Route('/{id}', methods: ['PATCH'])]
public function update(
    Product $product,
    #[MapRequestPayload] UpdateProductRequest $request,
    EntityManagerInterface $em,
): JsonResponse {
    $request->applyTo($product);
    $em->flush();

    return $this->json(ProductResponse::fromEntity($product));
}
```

### 5. **Nested DTO** (Complex Structures)

**Files:**
- `src/Dto/CreateOrderRequest.php`
- `src/Dto/OrderItemDto.php`

**OrderItemDto:**
```php
<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

readonly class OrderItemDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Positive]
        public int $productId,

        #[Assert\NotBlank]
        #[Assert\Positive]
        #[Assert\Range(min: 1, max: 999)]
        public int $quantity,

        #[Assert\Positive]
        public ?float $customPrice = null,
    ) {}
}
```

**CreateOrderRequest:**
```php
<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

readonly class CreateOrderRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Valid]
        #[Assert\Count(min: 1, max: 50)]
        /** @var OrderItemDto[] */
        public array $items,

        #[Assert\NotBlank]
        #[Assert\Length(max: 500)]
        public string $shippingAddress,

        #[Assert\Length(max: 1000)]
        public ?string $notes = null,

        #[Assert\Choice(choices: ['standard', 'express', 'overnight'])]
        public string $shippingMethod = 'standard',
    ) {}
}
```

### 6. **Collection DTO** (List Responses)

**File:**
- `src/Dto/ProductListResponse.php`

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\Dto;

readonly class ProductListResponse
{
    /**
     * @param ProductResponse[] $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
    ) {}

    public function getTotalPages(): int
    {
        return (int) ceil($this->total / $this->perPage);
    }

    public function toArray(): array
    {
        return [
            'items' => array_map(
                fn(ProductResponse $item) => $item->toArray(),
                $this->items
            ),
            'pagination' => [
                'total' => $this->total,
                'page' => $this->page,
                'per_page' => $this->perPage,
                'total_pages' => $this->getTotalPages(),
            ],
        ];
    }
}
```

## Common Validation Constraints

### **String Constraints**
```php
#[Assert\NotBlank]
#[Assert\Length(min: 3, max: 255)]
#[Assert\Regex(pattern: '/^[a-zA-Z0-9]+$/')]
#[Assert\Email]
#[Assert\Url]
#[Assert\Uuid]
```

### **Number Constraints**
```php
#[Assert\Positive]
#[Assert\PositiveOrZero]
#[Assert\Negative]
#[Assert\Range(min: 0, max: 100)]
#[Assert\GreaterThan(value: 0)]
#[Assert\LessThan(value: 100)]
#[Assert\DivisibleBy(value: 5)]
```

### **Collection Constraints**
```php
#[Assert\Count(min: 1, max: 10)]
#[Assert\Unique]
#[Assert\Valid] // Validate nested objects
#[Assert\All([
    new Assert\NotBlank(),
    new Assert\Length(min: 3),
])]
```

### **Date/Time Constraints**
```php
#[Assert\Date]
#[Assert\DateTime]
#[Assert\Time]
#[Assert\GreaterThan('today')]
#[Assert\LessThan('+1 year')]
```

### **Conditional Constraints**
```php
#[Assert\When(
    expression: 'this.getType() === "premium"',
    constraints: [
        new Assert\NotBlank(),
        new Assert\Range(min: 100),
    ],
)]
```

## Custom Validation

### **Custom Constraint**

**Constraint:**
```php
<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class UniqueEmail extends Constraint
{
    public string $message = 'Email "{{ value }}" is already in use.';
}
```

**Validator:**
```php
<?php

namespace App\Validator;

use App\Repository\UserRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UniqueEmailValidator extends ConstraintValidator
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (null === $value || '' === $value) {
            return;
        }

        $existing = $this->userRepository->findOneBy(['email' => $value]);

        if ($existing !== null) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }
}
```

**Usage:**
```php
readonly class CreateUserRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        #[UniqueEmail]
        public string $email,
    ) {}
}
```

## Testing DTOs

### **Validation Test**
```php
<?php

namespace App\Tests\Dto;

use App\Dto\CreateProductRequest;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CreateProductRequestTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testValidRequest(): void
    {
        $dto = new CreateProductRequest(
            name: 'Test Product',
            price: 99.99,
        );

        $violations = $this->validator->validate($dto);

        $this->assertCount(0, $violations);
    }

    public function testInvalidName(): void
    {
        $dto = new CreateProductRequest(
            name: 'ab', // Too short
            price: 99.99,
        );

        $violations = $this->validator->validate($dto);

        $this->assertGreaterThan(0, $violations->count());
        $this->assertEquals('name', $violations[0]->getPropertyPath());
    }

    public function testInvalidPrice(): void
    {
        $dto = new CreateProductRequest(
            name: 'Test Product',
            price: -10.00, // Negative
        );

        $violations = $this->validator->validate($dto);

        $this->assertGreaterThan(0, $violations->count());
    }
}
```

## Serialization

### **JSON Serialization**
```php
readonly class ProductResponse implements \JsonSerializable
{
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
        ];
    }
}
```

### **Symfony Serializer**
```php
use Symfony\Component\Serializer\Annotation\Groups;

readonly class ProductResponse
{
    public function __construct(
        #[Groups(['product:read', 'product:list'])]
        public int $id,

        #[Groups(['product:read', 'product:list'])]
        public string $name,

        #[Groups(['product:read'])]
        public ?string $description,
    ) {}
}

// Controller
public function show(Product $product, SerializerInterface $serializer): JsonResponse
{
    $response = ProductResponse::fromEntity($product);
    $json = $serializer->serialize($response, 'json', ['groups' => 'product:read']);

    return new JsonResponse($json, Response::HTTP_OK, [], true);
}
```

## Best Practices

**DTO Design**
- Use readonly classes for immutability
- Constructor property promotion for conciseness
- Clear, descriptive property names
- Include type hints for all properties

**Validation**
- Validate at DTO level, not controller
- Use built-in constraints when possible
- Create custom constraints for business rules
- Provide clear error messages

**Transformation**
- Static factory methods (fromEntity)
- Separate request and response DTOs
- Keep transformation logic in DTO
- Don't expose internal entity structure

**Organization**
- Group DTOs by feature (Product/, Order/)
- Suffix with purpose (Request, Response, Data)
- One DTO per use case
- Keep DTOs simple (data only, no logic)

**Performance**
- Use readonly for optimization
- Avoid heavy validation in DTOs
- Cache validation results if needed
- Use lazy loading for nested DTOs

Generate production-ready DTOs for clean data transfer between application layers.

## Next Steps

After creating DTOs, consider:
- `/api-new` — Create API endpoints that use these DTOs
- `/test-new` — Write validation tests for DTO constraints
- `/service-new` — Create a service that maps between DTOs and entities
