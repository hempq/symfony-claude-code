---
description: Create controllers with CRUD, forms, pagination, search, and dashboard patterns
model: claude-sonnet-4-6
effort: high
context: fork
argument-hint: "[describe controller, e.g. 'Dashboard controller with user stats and charts']"
---

Create a simple Symfony controller following best practices.

## Controller Specification

$ARGUMENTS

## Existing Project Context

Existing controllers:
!`find src/Controller -name "*Controller.php" -type f 2>/dev/null | sort`

Existing route prefixes:
!`grep -rh "#\[Route" src/Controller --include="*.php" 2>/dev/null | head -10`

Before generating, follow existing route naming and namespace conventions.

## What to Generate

Choose the controller type based on requirements:

### 1. **Simple Controller** (Static/Basic Pages)

**Files:**
- `src/Controller/[Name]Controller.php`
- `templates/[name]/index.html.twig`

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AboutController extends AbstractController
{
    #[Route('/about', name: 'about')]
    public function index(): Response
    {
        return $this->render('about/index.html.twig', [
            'title' => 'About Us',
        ]);
    }
}
```

### 2. **CRUD Controller** (List/Show/Create/Edit/Delete)

**Files:**
- `src/Controller/ProductController.php`
- `templates/product/index.html.twig`
- `templates/product/show.html.twig`
- `templates/product/new.html.twig`
- `templates/product/edit.html.twig`

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/products', name: 'products_')]
class ProductController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(ProductRepository $repository): Response
    {
        return $this->render('product/index.html.twig', [
            'products' => $repository->findAll(),
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'])]
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/new', name: 'new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($product);
            $em->flush();

            $this->addFlash('success', 'Product created!');

            return $this->redirectToRoute('products_show', ['id' => $product->getId()]);
        }

        return $this->render('product/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit')]
    public function edit(Product $product, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Product updated!');

            return $this->redirectToRoute('products_show', ['id' => $product->getId()]);
        }

        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Product $product, EntityManagerInterface $em): Response
    {
        $em->remove($product);
        $em->flush();

        $this->addFlash('success', 'Product deleted!');

        return $this->redirectToRoute('products_index');
    }
}
```

### 3. **API Controller** (JSON Responses)

**File:**
- `src/Controller/Api/ProductController.php`

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/products', name: 'api_products_')]
class ProductController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(ProductRepository $repository): JsonResponse
    {
        return $this->json([
            'data' => $repository->findAll(),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Product $product): JsonResponse
    {
        return $this->json([
            'data' => $product,
        ]);
    }
}
```

### 4. **Form Controller** (Single Form Page)

**Files:**
- `src/Controller/ContactController.php`
- `src/Form/ContactType.php`
- `templates/contact/index.html.twig`

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\Controller;

use App\Form\ContactType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'contact')]
    public function index(Request $request, MailerInterface $mailer): Response
    {
        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $email = (new Email())
                ->from($data['email'])
                ->to('admin@example.com')
                ->subject('Contact Form')
                ->text($data['message']);

            $mailer->send($email);

            $this->addFlash('success', 'Message sent!');

            return $this->redirectToRoute('contact');
        }

        return $this->render('contact/index.html.twig', [
            'form' => $form,
        ]);
    }
}
```

### 5. **Dashboard Controller** (Admin/Stats)

**File:**
- `src/Controller/DashboardController.php`
- `templates/dashboard/index.html.twig`

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard')]
    public function index(
        UserRepository $users,
        ProductRepository $products,
        OrderRepository $orders
    ): Response {
        return $this->render('dashboard/index.html.twig', [
            'stats' => [
                'users' => $users->count([]),
                'products' => $products->count([]),
                'orders' => $orders->count([]),
            ],
            'recentOrders' => $orders->findBy([], ['createdAt' => 'DESC'], 5),
        ]);
    }
}
```

## Advanced Patterns

### **Pagination**
```php
use Knp\Component\Pager\PaginatorInterface;

#[Route('/products', name: 'products_index')]
public function index(
    Request $request,
    ProductRepository $repository,
    PaginatorInterface $paginator
): Response {
    $query = $repository->createQueryBuilder('p')
        ->orderBy('p.createdAt', 'DESC')
        ->getQuery();

    $pagination = $paginator->paginate(
        $query,
        $request->query->getInt('page', 1),
        10 // items per page
    );

    return $this->render('product/index.html.twig', [
        'pagination' => $pagination,
    ]);
}
```

**Template with Pagination:**
```twig
{% for product in pagination %}
    {# Display product #}
{% endfor %}

<div class="navigation">
    {{ knp_pagination_render(pagination) }}
</div>
```

### **Search & Filtering**
```php
#[Route('/products', name: 'products_index')]
public function index(
    Request $request,
    ProductRepository $repository
): Response {
    $search = $request->query->get('q', '');
    $category = $request->query->get('category');
    $minPrice = $request->query->getInt('min_price', 0);
    $maxPrice = $request->query->getInt('max_price', 9999);

    $qb = $repository->createQueryBuilder('p');

    if ($search) {
        $qb->andWhere('p.name LIKE :search OR p.description LIKE :search')
           ->setParameter('search', "%{$search}%");
    }

    if ($category) {
        $qb->andWhere('p.category = :category')
           ->setParameter('category', $category);
    }

    $qb->andWhere('p.price BETWEEN :minPrice AND :maxPrice')
       ->setParameter('minPrice', $minPrice)
       ->setParameter('maxPrice', $maxPrice);

    $products = $qb->orderBy('p.createdAt', 'DESC')
                   ->getQuery()
                   ->getResult();

    return $this->render('product/index.html.twig', [
        'products' => $products,
        'filters' => [
            'search' => $search,
            'category' => $category,
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
        ],
    ]);
}
```

**Search Template:**
```twig
<form method="get" action="{{ path('products_index') }}">
    <input type="text" name="q" value="{{ filters.search }}" placeholder="Search...">
    <select name="category">
        <option value="">All Categories</option>
        <option value="1" {{ filters.category == 1 ? 'selected' : '' }}>Electronics</option>
    </select>
    <input type="number" name="min_price" value="{{ filters.min_price }}" placeholder="Min">
    <input type="number" name="max_price" value="{{ filters.max_price }}" placeholder="Max">
    <button type="submit">Search</button>
</form>
```

### **Sorting**
```php
#[Route('/products', name: 'products_index')]
public function index(
    Request $request,
    ProductRepository $repository
): Response {
    $sortBy = $request->query->get('sort', 'createdAt');
    $order = $request->query->get('order', 'DESC');

    $allowedSorts = ['name', 'price', 'createdAt'];
    $sortBy = in_array($sortBy, $allowedSorts) ? $sortBy : 'createdAt';
    $order = in_array($order, ['ASC', 'DESC']) ? $order : 'DESC';

    $products = $repository->findBy([], [$sortBy => $order]);

    return $this->render('product/index.html.twig', [
        'products' => $products,
        'sortBy' => $sortBy,
        'order' => $order,
    ]);
}
```

### **Bulk Actions**
```php
#[Route('/products/bulk-delete', name: 'products_bulk_delete', methods: ['POST'])]
public function bulkDelete(
    Request $request,
    ProductRepository $repository,
    EntityManagerInterface $em
): Response {
    $ids = $request->request->all('ids');

    if (empty($ids)) {
        $this->addFlash('error', 'No products selected');
        return $this->redirectToRoute('products_index');
    }

    $products = $repository->findBy(['id' => $ids]);

    foreach ($products as $product) {
        $em->remove($product);
    }

    $em->flush();

    $this->addFlash('success', sprintf('%d products deleted', count($products)));

    return $this->redirectToRoute('products_index');
}
```

### **Export to CSV/Excel**
```php
#[Route('/products/export', name: 'products_export')]
public function export(ProductRepository $repository): Response
{
    $products = $repository->findAll();

    $csv = "ID,Name,Price,Created\n";
    foreach ($products as $product) {
        $csv .= sprintf(
            "%d,%s,%s,%s\n",
            $product->getId(),
            $product->getName(),
            $product->getPrice(),
            $product->getCreatedAt()->format('Y-m-d')
        );
    }

    $response = new Response($csv);
    $response->headers->set('Content-Type', 'text/csv');
    $response->headers->set('Content-Disposition', 'attachment; filename="products.csv"');

    return $response;
}
```

## Common Patterns

### **Protected Routes**
```php
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    // All methods require ROLE_ADMIN
}

// Or per method
#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
public function users(): Response
```

### **Flash Messages**
```php
// Success
$this->addFlash('success', 'Operation completed!');

// Error
$this->addFlash('error', 'Something went wrong!');

// Warning
$this->addFlash('warning', 'Please check your input!');

// Info
$this->addFlash('info', 'New feature available!');
```

### **Redirects**
```php
// Redirect to route
return $this->redirectToRoute('products_index');

// With parameters
return $this->redirectToRoute('products_show', ['id' => 123]);

// External URL
return $this->redirect('https://example.com');
```

### **404 Not Found**
```php
// Automatic 404 with ParamConverter
public function show(Product $product): Response

// Manual 404
if (!$product) {
    throw $this->createNotFoundException('Product not found');
}
```

### **JSON Responses**
```php
// Success
return $this->json(['data' => $product]);

// With status code
return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);

// Serialize with groups
return $this->json($product, context: ['groups' => ['product:read']]);
```

## Template Examples

### **List Template**
```twig
{% extends 'base.html.twig' %}

{% block body %}
<div class="container">
    <h1>Products</h1>

    <a href="{{ path('products_new') }}" class="btn btn-primary">Add Product</a>

    <table class="table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Price</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            {% for product in products %}
            <tr>
                <td>{{ product.name }}</td>
                <td>{{ product.price|number_format(2) }} EUR</td>
                <td>
                    <a href="{{ path('products_show', {id: product.id}) }}">View</a>
                    <a href="{{ path('products_edit', {id: product.id}) }}">Edit</a>
                </td>
            </tr>
            {% else %}
            <tr>
                <td colspan="3">No products found</td>
            </tr>
            {% endfor %}
        </tbody>
    </table>
</div>
{% endblock %}
```

### **Form Template**
```twig
{% extends 'base.html.twig' %}

{% block body %}
<div class="container">
    <h1>{{ product.id ? 'Edit' : 'Create' }} Product</h1>

    {{ form_start(form) }}
        {{ form_row(form.name) }}
        {{ form_row(form.price) }}
        {{ form_row(form.description) }}

        <button type="submit" class="btn btn-primary">Save</button>
        <a href="{{ path('products_index') }}" class="btn btn-secondary">Cancel</a>
    {{ form_end(form) }}
</div>
{% endblock %}
```

## DDEV Commands

```bash
# Generate controller
ddev exec bin/console make:controller ProductController

# Generate CRUD (all at once)
ddev exec bin/console make:crud Product

# Check routes
ddev exec bin/console debug:router

# Check specific route
ddev exec bin/console debug:router products_index

# Clear cache
ddev exec bin/console cache:clear
```

## Best Practices

**Controller**
- Keep thin (business logic in services)
- Use strict types
- Type-hint all parameters
- Use attributes for routes
- Return Response objects

**Routing**
- Use route names for redirects
- Group related routes with class-level #[Route]
- Use requirements for parameters
- Use methods for HTTP verbs

**Security**
- Use #[IsGranted] for authorization
- CSRF protection automatic for forms
- Validate all input

**Error Handling**
- Use flash messages for user feedback
- Throw exceptions for errors
- Return proper HTTP status codes

Generate production-ready Symfony controllers that work seamlessly in DDEV environments.

## Next Steps

After creating a controller, consider:
- `/form-new` — Create forms used by this controller
- `/page-new` — Create full page templates with layout
- `/test-new` — Write functional tests for controller actions
- `/voter-new` — Add authorization voters for access control
