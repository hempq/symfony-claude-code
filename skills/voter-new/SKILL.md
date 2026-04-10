---
description: Create Symfony Security Voter for authorization
model: claude-sonnet-4-6
effort: high
---

Create a Symfony Security Voter following best practices.

## Voter Specification

$ARGUMENTS

## Existing Project Context

Existing voters:
!`find src/Security -name "*Voter.php" -type f 2>/dev/null | sort`

Before generating, check existing voters for permission naming and attribute conventions.

## What to Generate

Security Voters provide granular access control based on custom logic.

### 1. **Simple Voter** (Single Entity Permission)

**File:**
- `src/Security/Voter/ProductVoter.php`

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Product;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ProductVoter extends Voter
{
    public const VIEW = 'PRODUCT_VIEW';
    public const EDIT = 'PRODUCT_EDIT';
    public const DELETE = 'PRODUCT_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])
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

        return match ($attribute) {
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

        // Only owner can view unpublished products
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
        // Only owner can delete (not even admin)
        return $product->getOwner() === $user;
    }
}
```

### 2. **Advanced Voter** (Multiple Permissions & Complex Logic)

**File:**
- `src/Security/Voter/PostVoter.php`

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Post;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class PostVoter extends Voter
{
    public const CREATE = 'POST_CREATE';
    public const VIEW = 'POST_VIEW';
    public const EDIT = 'POST_EDIT';
    public const DELETE = 'POST_DELETE';
    public const PUBLISH = 'POST_PUBLISH';

    public function __construct(
        private readonly Security $security,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        $supportedAttributes = [
            self::CREATE,
            self::VIEW,
            self::EDIT,
            self::DELETE,
            self::PUBLISH,
        ];

        // CREATE doesn't require a subject
        if ($attribute === self::CREATE) {
            return true;
        }

        return in_array($attribute, $supportedAttributes)
            && $subject instanceof Post;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            // Anonymous users can only view published posts
            return $attribute === self::VIEW
                && $subject instanceof Post
                && $subject->isPublished();
        }

        return match ($attribute) {
            self::CREATE => $this->canCreate($user),
            self::VIEW => $this->canView($subject, $user),
            self::EDIT => $this->canEdit($subject, $user),
            self::DELETE => $this->canDelete($subject, $user),
            self::PUBLISH => $this->canPublish($subject, $user),
            default => false,
        };
    }

    private function canCreate(User $user): bool
    {
        // Only verified users can create posts
        return $user->isVerified();
    }

    private function canView(Post $post, User $user): bool
    {
        // Published posts are visible to everyone
        if ($post->isPublished()) {
            return true;
        }

        // Drafts are only visible to author, editors, and admins
        return $post->getAuthor() === $user
            || $this->security->isGranted('ROLE_EDITOR')
            || $this->security->isGranted('ROLE_ADMIN');
    }

    private function canEdit(Post $post, User $user): bool
    {
        // Author can always edit their own posts
        if ($post->getAuthor() === $user) {
            return true;
        }

        // Editors can edit any post
        if ($this->security->isGranted('ROLE_EDITOR')) {
            return true;
        }

        // Admins can edit any post
        return $this->security->isGranted('ROLE_ADMIN');
    }

    private function canDelete(Post $post, User $user): bool
    {
        // Author can delete within 24 hours
        if ($post->getAuthor() === $user) {
            $createdAt = $post->getCreatedAt();
            $now = new \DateTimeImmutable();
            $diff = $now->getTimestamp() - $createdAt->getTimestamp();

            return $diff < 86400; // 24 hours
        }

        // Only admins can delete any post
        return $this->security->isGranted('ROLE_ADMIN');
    }

    private function canPublish(Post $post, User $user): bool
    {
        // Only editors and admins can publish
        return $this->security->isGranted('ROLE_EDITOR')
            || $this->security->isGranted('ROLE_ADMIN');
    }
}
```

### 3. **Attribute-Based Voter** (No Specific Entity)

**File:**
- `src/Security/Voter/FeatureVoter.php`

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class FeatureVoter extends Voter
{
    public const BETA_FEATURES = 'FEATURE_BETA';
    public const PREMIUM_FEATURES = 'FEATURE_PREMIUM';
    public const ADMIN_PANEL = 'FEATURE_ADMIN_PANEL';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [
            self::BETA_FEATURES,
            self::PREMIUM_FEATURES,
            self::ADMIN_PANEL,
        ]);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        return match ($attribute) {
            self::BETA_FEATURES => $user->isBetaTester(),
            self::PREMIUM_FEATURES => $user->hasPremiumSubscription(),
            self::ADMIN_PANEL => in_array('ROLE_ADMIN', $user->getRoles()),
            default => false,
        };
    }
}
```

## Using Voters in Controllers

### **Check Access with #[IsGranted]**
```php
<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/products')]
class ProductController extends AbstractController
{
    #[Route('/{id}', name: 'product_show')]
    #[IsGranted('PRODUCT_VIEW', 'product')]
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/{id}/edit', name: 'product_edit')]
    #[IsGranted('PRODUCT_EDIT', 'product')]
    public function edit(Product $product): Response
    {
        return $this->render('product/edit.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/{id}/delete', name: 'product_delete', methods: ['POST'])]
    #[IsGranted('PRODUCT_DELETE', 'product')]
    public function delete(Product $product): Response
    {
        // Delete logic...
        return $this->redirectToRoute('products_index');
    }
}
```

### **Check Access Programmatically**
```php
public function show(Product $product): Response
{
    // Deny access if user cannot view
    $this->denyAccessUnlessGranted('PRODUCT_VIEW', $product);

    return $this->render('product/show.html.twig', [
        'product' => $product,
    ]);
}

public function edit(Product $product): Response
{
    // Check without throwing exception
    if (!$this->isGranted('PRODUCT_EDIT', $product)) {
        $this->addFlash('error', 'You cannot edit this product');
        return $this->redirectToRoute('products_index');
    }

    return $this->render('product/edit.html.twig', [
        'product' => $product,
    ]);
}
```

### **Check Multiple Permissions**
```php
public function manage(Product $product): Response
{
    // Check if user can edit OR delete
    if (!$this->isGranted('PRODUCT_EDIT', $product)
        && !$this->isGranted('PRODUCT_DELETE', $product)) {
        throw $this->createAccessDeniedException();
    }

    return $this->render('product/manage.html.twig', [
        'product' => $product,
        'canEdit' => $this->isGranted('PRODUCT_EDIT', $product),
        'canDelete' => $this->isGranted('PRODUCT_DELETE', $product),
    ]);
}
```

## Using Voters in Twig Templates

### **Conditional Rendering**
```twig
{% if is_granted('PRODUCT_EDIT', product) %}
    <a href="{{ path('product_edit', {id: product.id}) }}" class="btn btn-primary">
        Edit Product
    </a>
{% endif %}

{% if is_granted('PRODUCT_DELETE', product) %}
    <form method="post" action="{{ path('product_delete', {id: product.id}) }}">
        <button type="submit" class="btn btn-danger">Delete</button>
    </form>
{% endif %}

{% if is_granted('PRODUCT_VIEW', product) %}
    <p>{{ product.description }}</p>
{% else %}
    <p>You don't have permission to view this product.</p>
{% endif %}
```

### **Show/Hide UI Elements**
```twig
<div class="product-actions">
    {% if is_granted('PRODUCT_VIEW', product) %}
        <a href="{{ path('product_show', {id: product.id}) }}">View</a>
    {% endif %}

    {% if is_granted('PRODUCT_EDIT', product) %}
        <a href="{{ path('product_edit', {id: product.id}) }}">Edit</a>
    {% endif %}

    {% if is_granted('PRODUCT_DELETE', product) %}
        <button onclick="deleteProduct({{ product.id }})">Delete</button>
    {% endif %}
</div>
```

## Using Voters in Services

```php
<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Product;
use Symfony\Bundle\SecurityBundle\Security;

class ProductService
{
    public function __construct(
        private readonly Security $security,
    ) {}

    public function canUserManageProduct(Product $product): bool
    {
        return $this->security->isGranted('PRODUCT_EDIT', $product)
            || $this->security->isGranted('PRODUCT_DELETE', $product);
    }

    public function deleteProduct(Product $product): void
    {
        if (!$this->security->isGranted('PRODUCT_DELETE', $product)) {
            throw new \RuntimeException('Access denied');
        }

        // Delete logic...
    }
}
```

## Testing Voters

### **Unit Test**
```php
<?php

declare(strict_types=1);

namespace App\Tests\Security\Voter;

use App\Entity\Product;
use App\Entity\User;
use App\Security\Voter\ProductVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class ProductVoterTest extends TestCase
{
    private ProductVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new ProductVoter();
    }

    public function testOwnerCanEditProduct(): void
    {
        $user = new User();
        $product = new Product();
        $product->setOwner($user);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $result = $this->voter->vote($token, $product, [ProductVoter::EDIT]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testNonOwnerCannotEditProduct(): void
    {
        $owner = new User();
        $otherUser = new User();
        $product = new Product();
        $product->setOwner($owner);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($otherUser);

        $result = $this->voter->vote($token, $product, [ProductVoter::EDIT]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testAdminCanEditProduct(): void
    {
        $owner = new User();
        $admin = new User();
        $admin->setRoles(['ROLE_ADMIN']);

        $product = new Product();
        $product->setOwner($owner);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($admin);

        $result = $this->voter->vote($token, $product, [ProductVoter::EDIT]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }
}
```

### **Integration Test**
```php
<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Product;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProductControllerSecurityTest extends WebTestCase
{
    public function testOwnerCanAccessEditPage(): void
    {
        $client = static::createClient();

        $user = new User();
        $user->setEmail('owner@example.com');
        $client->loginUser($user);

        // Assume product with ID 1 belongs to this user
        $client->request('GET', '/products/1/edit');

        $this->assertResponseIsSuccessful();
    }

    public function testNonOwnerCannotAccessEditPage(): void
    {
        $client = static::createClient();

        $user = new User();
        $user->setEmail('other@example.com');
        $client->loginUser($user);

        // Assume product with ID 1 belongs to someone else
        $client->request('GET', '/products/1/edit');

        $this->assertResponseStatusCodeSame(403);
    }
}
```

## Common Patterns

### **Owner-Based Access**
```php
private function canEdit(Product $product, User $user): bool
{
    return $product->getOwner() === $user;
}
```

### **Role-Based Access**
```php
private function canEdit(Product $product, User $user): bool
{
    return in_array('ROLE_ADMIN', $user->getRoles());
}
```

### **Combination Access**
```php
private function canEdit(Product $product, User $user): bool
{
    return $product->getOwner() === $user
        || in_array('ROLE_ADMIN', $user->getRoles());
}
```

### **Time-Based Access**
```php
private function canDelete(Post $post, User $user): bool
{
    if ($post->getAuthor() !== $user) {
        return false;
    }

    $hoursSinceCreation = (new \DateTimeImmutable())->getTimestamp()
        - $post->getCreatedAt()->getTimestamp();

    return $hoursSinceCreation < 3600; // 1 hour
}
```

### **Status-Based Access**
```php
private function canView(Product $product, User $user): bool
{
    if ($product->isPublished()) {
        return true;
    }

    return $product->getOwner() === $user;
}
```

## Best Practices

**Voter Design**
- One voter per entity or permission domain
- Use constants for permission names
- Keep logic in private methods
- Return false by default

**Naming Conventions**
- Use ENTITY_ACTION format (e.g., PRODUCT_EDIT)
- Be specific (PRODUCT_PUBLISH vs PRODUCT_EDIT)
- Use consistent naming across voters

**Security**
- Always check user is logged in
- Deny access by default
- Use explicit permission checks
- Log security decisions for audit

**Performance**
- Keep voter logic simple and fast
- Avoid database queries in voters
- Cache expensive checks
- Use early returns

**Testing**
- Test all permission scenarios
- Test with different user roles
- Test edge cases (null user, missing data)
- Test integration with controllers

Generate production-ready Security Voters for fine-grained authorization in Symfony applications.

## Next Steps

After creating a voter, consider:
- `/api-protect` — Apply the voter to API endpoints
- `/test-new` — Write unit tests for voter logic
