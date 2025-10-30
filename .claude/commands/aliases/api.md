---
description: Create a new Symfony API endpoint (alias for api-new)
model: claude-sonnet-4-5
---

This is an alias for `/api-new`. Redirecting...

$ARGUMENTS

---

Create a new Symfony API endpoint with validation, error handling, and type safety.

## API Specification

$ARGUMENTS

## What to Generate

Choose the API pattern based on requirements:

### 1. **Simple GET Endpoint** (Read Single Resource)

**File:**
- `src/Controller/Api/ProductController.php`

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/products', name: 'api_products_')]
class ProductController extends AbstractController
{
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Product $product): JsonResponse
    {
        return $this->json([
            'id' => $product->getId(),
            'name' => $product->getName(),
            'price' => $product->getPrice(),
            'description' => $product->getDescription(),
        ]);
    }
}
```

For full API command documentation, use `/api-new` or see `.claude/commands/api/api-new.md`.
