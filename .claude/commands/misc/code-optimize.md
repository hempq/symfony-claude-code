---
description: Analyze and optimize code for performance, memory, and efficiency
model: claude-sonnet-4-5
---

Optimize the following PHP/Symfony code for performance and efficiency.

## Code to Optimize

$ARGUMENTS

## Optimization Strategy for Symfony Developers

### 1. **Profiling First**
- Identify actual bottlenecks with Symfony Profiler
- Don't optimize prematurely
- Measure before and after
- Focus on high-impact areas

### 2. **Performance Optimization Areas**

**Symfony/Twig**
- Use Twig template caching (automatic in prod)
- Fragment caching with ESI or SSI
- HTTP caching with attributes
- Asset optimization (AssetMapper or Webpack Encore)
- Lazy load services
- Preload critical services

**Doctrine ORM**
- Add indexes to frequently queried columns
- Eager load associations (avoid N+1 queries)
- Use partial objects for large datasets
- Implement pagination
- Use DQL for complex queries
- Query result caching
- Second-level cache for read-heavy data

**Database**
- Index foreign keys
- Use EXPLAIN to analyze queries
- Batch inserts/updates
- Connection pooling
- Optimize table structure
- Use database views for complex queries

**HTTP/Caching**
- Implement HTTP caching
- Use Varnish or Symfony HttpCache
- Set proper cache headers
- Use ETags for validation
- CDN for static assets

**Memory**
- Use generators for large datasets
- Clear entity manager periodically in batch operations
- Unset large variables when done
- Use readonly properties to reduce memory
- Avoid loading unnecessary data

### 3. **Optimization Checklist**

**PHP**
- ✅ Use strict types
- ✅ Use opcache in production
- ✅ Minimize autoloading overhead
- ✅ Use generators for large datasets
- ✅ Avoid unnecessary object creation

**Doctrine**
- ✅ Add indexes on frequently queried fields
- ✅ Use Query Builder over DQL when appropriate
- ✅ Eager load associations to avoid N+1
- ✅ Use partial objects for listings
- ✅ Implement pagination
- ✅ Clear EntityManager in batch operations

**Symfony**
- ✅ Use prod environment with opcache
- ✅ Precompile container (cache:warmup)
- ✅ Use lazy services
- ✅ Minimize EventSubscribers
- ✅ Cache routes and translations

**Twig**
- ✅ Enable template caching (auto in prod)
- ✅ Use fragment caching
- ✅ Minimize template complexity
- ✅ Avoid expensive operations in templates
- ✅ Use Twig components for reusability

### 4. **Measurement Tools**

**Symfony**
- Symfony Profiler (web debug toolbar)
- Blackfire.io for profiling
- Xdebug profiling
- php-spx profiler

**Database**
- Doctrine query profiler
- MySQL slow query log
- EXPLAIN queries
- Database metrics (connections, query time)

**Server**
- Apache Bench (ab)
- wrk for load testing
- k6 for modern load testing
- New Relic / DataDog APM

### 5. **Common Optimizations**

**Avoid N+1 Queries**
```php
// ❌ Before: N+1 queries (1 + N queries for N products)
$products = $productRepository->findAll();
foreach ($products as $product) {
    echo $product->getCategory()->getName(); // Lazy load = query per product
}

// ✅ After: 1 query with JOIN
$products = $productRepository->createQueryBuilder('p')
    ->leftJoin('p.category', 'c')
    ->addSelect('c')
    ->getQuery()
    ->getResult();

foreach ($products as $product) {
    echo $product->getCategory()->getName(); // Already loaded
}
```

**Use Pagination**
```php
// ❌ Before: Load all records
public function list(ProductRepository $repo): Response
{
    $products = $repo->findAll(); // Loads thousands of records

    return $this->render('products/list.html.twig', [
        'products' => $products,
    ]);
}

// ✅ After: Paginate
public function list(
    Request $request,
    ProductRepository $repo,
    PaginatorInterface $paginator
): Response {
    $query = $repo->createQueryBuilder('p')
        ->orderBy('p.createdAt', 'DESC')
        ->getQuery();

    $pagination = $paginator->paginate(
        $query,
        $request->query->getInt('page', 1),
        20 // items per page
    );

    return $this->render('products/list.html.twig', [
        'pagination' => $pagination,
    ]);
}
```

**Add Database Indexes**
```php
// ❌ Before: No index on frequently queried field
#[ORM\Column(type: Types::STRING, length: 180)]
private string $email;

// ✅ After: Index for faster lookups
#[ORM\Column(type: Types::STRING, length: 180, unique: true)]
#[ORM\Index(name: 'idx_email', columns: ['email'])]
private string $email;
```

**HTTP Caching**
```php
// ❌ Before: No caching
#[Route('/products/{id}')]
public function show(Product $product): Response
{
    return $this->render('products/show.html.twig', [
        'product' => $product,
    ]);
}

// ✅ After: HTTP caching
#[Route('/products/{id}')]
#[Cache(maxage: 3600, public: true)]
public function show(Product $product): Response
{
    $response = $this->render('products/show.html.twig', [
        'product' => $product,
    ]);

    // Set ETag for validation
    $response->setETag(md5($product->getUpdatedAt()->format('c')));
    $response->setPublic();
    $response->setMaxAge(3600);

    return $response;
}
```

**Use Generators for Large Datasets**
```php
// ❌ Before: Load all into memory
public function exportUsers(UserRepository $repo): void
{
    $users = $repo->findAll(); // Loads all users into memory

    foreach ($users as $user) {
        $this->exportLine($user);
    }
}

// ✅ After: Stream with generator
class UserRepository extends ServiceEntityRepository
{
    public function iterateAll(): \Generator
    {
        $qb = $this->createQueryBuilder('u');
        $query = $qb->getQuery();

        foreach ($query->toIterable() as $user) {
            yield $user;
        }
    }
}

public function exportUsers(UserRepository $repo): void
{
    foreach ($repo->iterateAll() as $user) {
        $this->exportLine($user);

        // Clear every 100 users to free memory
        if (($i++ % 100) === 0) {
            $this->em->clear();
        }
    }
}
```

**Query Result Caching**
```php
// ❌ Before: Query runs every time
public function getPopularProducts(): array
{
    return $this->createQueryBuilder('p')
        ->where('p.views > :threshold')
        ->setParameter('threshold', 1000)
        ->orderBy('p.views', 'DESC')
        ->setMaxResults(10)
        ->getQuery()
        ->getResult();
}

// ✅ After: Cache results for 1 hour
public function getPopularProducts(): array
{
    return $this->createQueryBuilder('p')
        ->where('p.views > :threshold')
        ->setParameter('threshold', 1000)
        ->orderBy('p.views', 'DESC')
        ->setMaxResults(10)
        ->getQuery()
        ->enableResultCache(3600, 'popular_products')
        ->getResult();
}
```

**Partial Object Loading**
```php
// ❌ Before: Load entire entity
public function listProducts(): array
{
    return $this->createQueryBuilder('p')
        ->getQuery()
        ->getResult(); // Loads all fields
}

// ✅ After: Load only needed fields
public function listProducts(): array
{
    return $this->createQueryBuilder('p')
        ->select('p.id', 'p.name', 'p.price')
        ->getQuery()
        ->getArrayResult(); // Returns arrays, not objects (less memory)
}
```

**Batch Operations**
```php
// ❌ Before: Individual inserts
foreach ($data as $item) {
    $product = new Product();
    $product->setName($item['name']);
    $this->em->persist($product);
    $this->em->flush(); // Flush after each = N queries
}

// ✅ After: Batch with periodic flush
foreach ($data as $i => $item) {
    $product = new Product();
    $product->setName($item['name']);
    $this->em->persist($product);

    // Flush every 100 items
    if (($i % 100) === 0) {
        $this->em->flush();
        $this->em->clear(); // Clear to free memory
    }
}

// Flush remaining
$this->em->flush();
$this->em->clear();
```

### 6. **Symfony-Specific Optimizations**

**Lazy Services**
```yaml
# config/services.yaml
services:
    App\Service\HeavyService:
        lazy: true  # Only instantiated when actually used
```

**Preload Services**
```yaml
# config/services.yaml
services:
    App\Service\CriticalService:
        tags: ['container.preload']
```

**Fragment Caching**
```twig
{# Cache expensive partial for 1 hour #}
{% cache 'product_reviews_' ~ product.id ttl(3600) %}
    {{ render(controller('App\\Controller\\ReviewController::list', {
        productId: product.id
    })) }}
{% endcache %}
```

**Asset Optimization**
```bash
# Compile and minify assets
ddev exec npm run build

# Or use AssetMapper (Symfony 6.3+)
ddev exec bin/console asset-map:compile
```

## DDEV Performance Commands

```bash
# Enable opcache in DDEV
# Edit .ddev/php/opcache.ini and restart

# Warm cache
ddev exec bin/console cache:warmup --env=prod

# Clear all caches
ddev exec bin/console cache:clear

# Analyze Doctrine queries
ddev exec bin/console doctrine:query:sql "EXPLAIN SELECT ..."

# Profile with Blackfire
ddev blackfire curl https://myproject.ddev.site/slow-page

# Load testing
ddev exec ab -n 1000 -c 10 https://myproject.ddev.site/
```

## Output Format

1. **Analysis** - Identify performance bottlenecks (N+1 queries, missing indexes, etc.)
2. **Optimized Code** - Improved version with explanations
3. **Benchmarks** - Expected improvement (queries reduced, response time)
4. **Database Changes** - Indexes to add, schema optimizations
5. **Configuration** - Caching, opcache settings
6. **Trade-offs** - Complexity added, cache invalidation concerns
7. **Monitoring** - How to measure improvements

Focus on practical, measurable optimizations that provide real user value. Don't sacrifice readability for micro-optimizations. Always measure with Symfony Profiler or Blackfire.
