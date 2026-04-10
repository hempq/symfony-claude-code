---
description: Create a new Symfony controller and template with modern best practices
model: claude-sonnet-4-6
effort: medium
---

Create a new Symfony page (controller + template) following best practices.

## Page Specification

$ARGUMENTS

## Existing Project Context

Existing templates:
!`ls templates/ 2>/dev/null`

Base template:
!`head -10 templates/base.html.twig 2>/dev/null`

Before generating, follow existing template structure and layout inheritance.

## What to Generate

Create controller and template based on the page type:

### 1. **Simple Page** (Static Content)

**Files:**
- `src/Controller/[Name]Controller.php`
- `templates/[name]/index.html.twig`

**Example:**
```php
<?php
// src/Controller/AboutController.php
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
        return $this->render('about/index.html.twig');
    }
}
```

```twig
{# templates/about/index.html.twig #}
{% extends 'base.html.twig' %}

{% block title %}About Us{% endblock %}

{% block body %}
    <div class="container">
        <h1>About Us</h1>
        <p>Content goes here...</p>
    </div>
{% endblock %}
```

### 2. **List Page** (With Database)

**Files:**
- `src/Controller/[Name]Controller.php`
- `templates/[name]/index.html.twig`

**Example:**
```php
<?php
// src/Controller/ProductController.php
declare(strict_types=1);

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/products', name: 'products_')]
class ProductController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(ProductRepository $repository): Response
    {
        $products = $repository->findAll();

        return $this->render('products/index.html.twig', [
            'products' => $products,
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'])]
    public function show(int $id, ProductRepository $repository): Response
    {
        $product = $repository->find($id);

        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }

        return $this->render('products/show.html.twig', [
            'product' => $product,
        ]);
    }
}
```

```twig
{# templates/products/index.html.twig #}
{% extends 'base.html.twig' %}

{% block title %}Products{% endblock %}

{% block body %}
    <div class="container">
        <h1>Products</h1>

        <div class="product-grid">
            {% for product in products %}
                <div class="product-card">
                    <h3>{{ product.name }}</h3>
                    <p>{{ product.price|number_format(2) }} EUR</p>
                    <a href="{{ path('products_show', {id: product.id}) }}">
                        View Details
                    </a>
                </div>
            {% else %}
                <p>No products found.</p>
            {% endfor %}
        </div>
    </div>
{% endblock %}
```

### 3. **Form Page** (Create/Edit)

**Files:**
- `src/Controller/[Name]Controller.php`
- `src/Form/[Name]Type.php`
- `templates/[name]/new.html.twig`

**Example:**
```php
<?php
// src/Controller/ProductController.php
declare(strict_types=1);

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/products', name: 'products_')]
class ProductController extends AbstractController
{
    #[Route('/new', name: 'new')]
    public function new(
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($product);
            $em->flush();

            $this->addFlash('success', 'Product created successfully!');

            return $this->redirectToRoute('products_index');
        }

        return $this->render('products/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit')]
    public function edit(
        Product $product,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $form = $this->createForm(ProductType::class, $product);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Product updated successfully!');

            return $this->redirectToRoute('products_index');
        }

        return $this->render('products/edit.html.twig', [
            'form' => $form,
            'product' => $product,
        ]);
    }
}
```

```php
<?php
// src/Form/ProductType.php
declare(strict_types=1);

namespace App\Form;

use App\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Product Name',
                'attr' => ['placeholder' => 'Enter product name'],
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Price',
                'currency' => 'EUR',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
```

```twig
{# templates/products/new.html.twig #}
{% extends 'base.html.twig' %}

{% block title %}Create Product{% endblock %}

{% block body %}
    <div class="container">
        <h1>Create Product</h1>

        {{ form_start(form) }}
            {{ form_widget(form) }}

            <button type="submit" class="btn btn-primary">Create</button>
            <a href="{{ path('products_index') }}" class="btn btn-secondary">Cancel</a>
        {{ form_end(form) }}
    </div>
{% endblock %}
```

### 4. **Paginated List**

**Example:**
```php
<?php
// src/Controller/ArticleController.php
declare(strict_types=1);

namespace App\Controller;

use App\Repository\ArticleRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ArticleController extends AbstractController
{
    #[Route('/articles', name: 'articles_index')]
    public function index(
        Request $request,
        ArticleRepository $repository,
        PaginatorInterface $paginator
    ): Response {
        $query = $repository->createQueryBuilder('a')
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10 // items per page
        );

        return $this->render('articles/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }
}
```

```twig
{# templates/articles/index.html.twig #}
{% extends 'base.html.twig' %}

{% block body %}
    <div class="container">
        <h1>Articles</h1>

        {% for article in pagination %}
            <article>
                <h2>{{ article.title }}</h2>
                <p>{{ article.excerpt }}</p>
                <a href="{{ path('articles_show', {id: article.id}) }}">Read more</a>
            </article>
        {% endfor %}

        {# Pagination links #}
        {{ knp_pagination_render(pagination) }}
    </div>
{% endblock %}
```

### 5. **Dashboard Page** (Multiple Data Sources)

**Example:**
```php
<?php
// src/Controller/DashboardController.php
declare(strict_types=1);

namespace App\Controller;

use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard')]
    public function index(
        OrderRepository $orderRepo,
        ProductRepository $productRepo,
        UserRepository $userRepo
    ): Response {
        return $this->render('dashboard/index.html.twig', [
            'totalOrders' => $orderRepo->count([]),
            'totalProducts' => $productRepo->count([]),
            'totalUsers' => $userRepo->count([]),
            'recentOrders' => $orderRepo->findBy([], ['createdAt' => 'DESC'], 5),
        ]);
    }
}
```

## Common Patterns

### **Flash Messages**
```php
// In controller
$this->addFlash('success', 'Operation completed!');
$this->addFlash('error', 'Something went wrong!');
```

```twig
{# In template #}
{% for message in app.flashes('success') %}
    <div class="alert alert-success">{{ message }}</div>
{% endfor %}
```

### **Route Parameters**
```php
#[Route('/products/{slug}', name: 'products_show')]
public function show(string $slug): Response
{
    // Use $slug
}

#[Route('/category/{id}/products', name: 'category_products', requirements: ['id' => '\d+'])]
public function byCategory(int $id): Response
{
    // Use $id (only accepts digits)
}
```

### **Query Parameters**
```php
public function search(Request $request): Response
{
    $query = $request->query->get('q', '');
    $page = $request->query->getInt('page', 1);

    // Use $query and $page
}
```

### **Redirects**
```php
// Redirect to route
return $this->redirectToRoute('products_index');

// Redirect to route with parameters
return $this->redirectToRoute('products_show', ['id' => 123]);

// Redirect to URL
return $this->redirect('https://example.com');
```

### **JSON Response**
```php
#[Route('/api/products', name: 'api_products')]
public function apiList(ProductRepository $repository): Response
{
    $products = $repository->findAll();

    return $this->json($products);
}
```

## DDEV Commands

```bash
# Create controller (with make:controller)
ddev exec bin/console make:controller ProductController

# Create entity
ddev exec bin/console make:entity Product

# Create form
ddev exec bin/console make:form ProductType

# Generate CRUD (all pages at once)
ddev exec bin/console make:crud Product

# Clear cache
ddev exec bin/console cache:clear

# Check routes
ddev exec bin/console debug:router
```

## Best Practices

### **Controller**
- Keep controllers thin (use services for business logic)
- Use strict types (`declare(strict_types=1)`)
- Type-hint all parameters
- Use route attributes (not YAML)
- Return Response objects

### **Templates**
- Extend `base.html.twig`
- Use blocks for customization
- Extract reusable parts to components
- Use `path()` for routes, `asset()` for assets
- Escape user input (Twig auto-escapes)

### **Security**
- Use `#[IsGranted('ROLE_ADMIN')]` for protected pages
- Check permissions with `$this->denyAccessUnlessGranted()`
- Use CSRF protection for forms (automatic)
- Validate form input

### **Performance**
- Use Doctrine lazy loading
- Paginate large result sets
- Cache rendered templates (automatic in prod)
- Use HTTP caching for static pages

Generate production-ready Symfony controllers and templates that work seamlessly in DDEV environments.

## Next Steps

After creating a page, consider:
- `/component-new` — Extract reusable parts into Symfony UX components
- `/test-new` — Write functional tests for the page
