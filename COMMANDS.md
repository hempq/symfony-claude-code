# Command Reference

Quick reference guide for all 21 slash commands in this Symfony Claude Code plugin.

## 🚀 Quick Start

All commands follow the same pattern:
```bash
/command-name [optional description or requirements]
```

Commands automatically generate production-ready code following Symfony 7+ best practices with PHP 8.2+ features.

### Command Aliases

For faster usage, common commands have shorter aliases:

| Alias | Full Command | Description |
|-------|-------------|-------------|
| `/api` | `/api-new` | Create API endpoint |
| `/entity` | `/entity-new` | Create Doctrine entity |
| `/test` | `/test-new` | Create PHPUnit tests |
| `/svc` | `/service-new` | Create service |
| `/ctrl` | `/controller-new` | Create controller |

---

## 📋 Development Commands (11)

### /new-task
**Analyze task complexity and create implementation plan**

Breaks down complex tasks into actionable steps with technical specifications.

```bash
/new-task Add user authentication with JWT and refresh tokens
```

---

### /service-new
**Create Symfony service with business logic**

Generates services with dependency injection, type safety, and modern PHP patterns.

**Patterns:** Simple Service, External API, Caching, Events, File Upload, Email

```bash
/service-new Create a product service with CRUD operations
```

---

### /test-new
**Create PHPUnit tests**

Generates comprehensive tests with proper assertions and mocking.

**Test Types:** Unit Test, Integration Test, Functional Test, Repository Test, Form Test, Entity Test

```bash
/test-new Create unit tests for ProductService
```

---

### /dto-new
**Create Data Transfer Objects with validation**

Generates DTOs for clean data transfer between layers with Symfony validation constraints.

**DTO Types:** API Request, API Response, Form Data, Update DTO, Nested DTO, Collection DTO

```bash
/dto-new Create a DTO for product creation API endpoint
```

---

### /event-new
**Create Symfony event with listener or subscriber**

Generates events and listeners for event-driven architecture.

**Types:** Custom Event + Listener, Event Subscriber, Doctrine Event Listener, Kernel Event Subscriber

```bash
/event-new Create UserRegistered event with email notification listener
```

---

### /code-explain
**Generate detailed code explanations**

Analyzes and explains code with architecture insights.

```bash
/code-explain Explain the ProductService class
```

---

### /code-optimize
**Optimize code for performance**

Analyzes and optimizes code for better performance, memory usage, and efficiency.

```bash
/code-optimize Optimize the product listing query
```

---

### /code-cleanup
**Refactor and clean up code**

Refactors code following clean code principles and Symfony best practices.

```bash
/code-cleanup Clean up the ProductController
```

---

### /feature-plan
**Plan feature implementation**

Creates detailed technical specifications for new features.

```bash
/feature-plan Plan a shopping cart feature
```

---

### /lint
**Run linting and fix issues**

Runs PHP CS Fixer, PHPStan, and other linting tools.

```bash
/lint
```

---

### /docs-generate
**Generate documentation**

Creates comprehensive documentation for code, APIs, and components.

```bash
/docs-generate Generate API documentation
```

---

## 🔌 API Commands (3)

### /api-new
**Create new API endpoint**

Generates REST API endpoints with validation, error handling, and type safety.

**Patterns:** Simple GET, POST with validation, PATCH update, DELETE, Collection with pagination

```bash
/api-new Create API endpoint for product management
```

**Generates:**
- Controller with `#[Route]` attributes
- Request/Response DTOs
- Validation with `#[MapRequestPayload]`
- Proper HTTP status codes
- Error handling

---

### /api-test
**Test API endpoints**

Generates functional tests for API endpoints with WebTestCase.

```bash
/api-test Create tests for product API
```

**Generates:**
- Success scenario tests
- Validation error tests
- Authentication tests
- Edge case tests

---

### /api-protect
**Add authentication and authorization**

Adds JWT authentication, Security Voters, rate limiting, and CORS configuration.

**Features:** JWT Auth, Security Voters, Rate Limiting, CORS, Input Sanitization

```bash
/api-protect Add JWT authentication to product API
```

---

## 🎨 UI Commands (4)

### /controller-new
**Create simple Symfony controller**

Generates controllers with routes and templates.

**Patterns:** Simple Controller, CRUD Controller, API Controller, Form Controller, Dashboard Controller

```bash
/controller-new Create a product CRUD controller
```

**Generates:**
- Controller with attribute-based routing
- Twig templates
- Flash messages
- Redirects

---

### /form-new
**Create Symfony form with validation**

Generates FormType classes with validation constraints.

**Form Types:** Entity Form, DTO Form, Complex Form with relations

```bash
/form-new Create a product form with validation
```

**Generates:**
- FormType class
- Entity with validation constraints
- All common field types
- Custom validation

---

### /component-new
**Create Symfony UX component**

Generates Twig Components, Live Components, or Stimulus controllers.

**Patterns:** Twig Component, Live Component, Stimulus Controller

```bash
/component-new Create a live search component
```

**Generates:**
- Component class with properties
- Twig template
- Stimulus controller (if needed)
- Live actions (if Live Component)

---

### /page-new
**Create full page with controller and template**

Generates complete pages with controllers, routes, and styled templates.

**Patterns:** Static Page, List Page, Detail Page, Form Page, Dashboard Page

```bash
/page-new Create a product listing page with pagination
```

**Generates:**
- Controller with route
- Twig template with layout
- Data fetching logic
- Pagination (if needed)

---

## 💾 Database Commands (2)

### /entity-new
**Create Doctrine entity with validation**

Generates entities with validation, migrations, and repositories.

**Workflows:** Code-First (recommended), Database-First

```bash
/entity-new Create a Product entity with name, price, and description
```

**Generates:**
- Entity class with PHP 8 attributes
- Validation constraints
- Repository
- Migration file

**Common Patterns:**
- Relationships (OneToMany, ManyToMany, OneToOne)
- Enums (PHP 8.1+)
- Timestamps with lifecycle callbacks
- Indexes

---

### /messenger-new
**Create Symfony Messenger handler for async tasks**

Generates message classes and handlers for asynchronous processing.

**Patterns:** Command Message, Event Message, Scheduled Task, Webhook Handler

```bash
/messenger-new Create a message handler for sending welcome emails
```

**Generates:**
- Message class (readonly)
- MessageHandler with `#[AsMessageHandler]`
- Configuration for transports
- Retry strategy

**Use Cases:**
- Email sending
- Image processing
- External API calls
- Background jobs

---

## 🔒 Security Commands (1)

### /voter-new
**Create Security Voter for authorization**

Generates Symfony Security Voters for granular access control.

**Patterns:** Simple Voter, Advanced Voter, Attribute-Based Voter

```bash
/voter-new Create a voter for product edit permissions
```

**Generates:**
- Voter class with `supports()` and `voteOnAttribute()`
- Permission constants
- Logic methods (canView, canEdit, canDelete)
- Usage examples for controllers and Twig

**Common Access Patterns:**
- Owner-based access
- Role-based access
- Time-based access
- Status-based access

---

## 🤖 Specialized AI Agents (11)

These agents are automatically invoked based on context or can be explicitly requested.

### Architecture & Planning
- **tech-stack-researcher** - Technology recommendations with trade-offs
- **system-architect** - Scalable system architecture design
- **backend-architect** - Backend systems with data integrity
- **frontend-architect** - Performant, accessible UI architecture
- **requirements-analyst** - Transform ideas into specifications

### Code Quality & Performance
- **refactoring-expert** - Systematic refactoring and clean code
- **performance-engineer** - Measurement-driven optimization
- **security-engineer** - Vulnerability identification

### Documentation & Research
- **technical-writer** - Clear, comprehensive documentation
- **learning-guide** - Teaching programming concepts
- **deep-research-agent** - Comprehensive research with adaptive strategies

---

## 💡 Tips & Best Practices

### Command Usage Tips

1. **Be Specific**: Provide clear requirements
   ```bash
   # ❌ Vague
   /service-new Create a service

   # ✅ Specific
   /service-new Create a product service with CRUD operations and caching
   ```

2. **Combine Commands**: Use multiple commands for complete features
   ```bash
   /entity-new Create Product entity with name, price, category
   /form-new Create ProductType form for the Product entity
   /controller-new Create ProductController with CRUD operations
   /test-new Create tests for ProductController
   ```

3. **Leverage Patterns**: Reference specific patterns from the command docs
   ```bash
   /component-new Create a Live Component for real-time product search
   /api-new Create a REST API with pagination for products
   ```

### Symfony + DDEV Workflow

```bash
# 1. Create entity
/entity-new Create User entity with email, password, roles

# 2. Generate migration (manual)
ddev exec bin/console make:migration
ddev exec bin/console doctrine:migrations:migrate

# 3. Create form
/form-new Create UserRegistrationForm

# 4. Create controller
/controller-new Create RegistrationController with form handling

# 5. Create tests
/test-new Create functional tests for user registration

# 6. Run tests
ddev exec bin/phpunit
```

### Common Patterns

**API Development:**
```bash
/entity-new Create Product entity
/api-new Create product CRUD API
/api-protect Add JWT authentication
/api-test Create API tests
```

**Form Development:**
```bash
/entity-new Create Product entity
/form-new Create ProductType form
/controller-new Create ProductController with form
/test-new Create form tests
```

**Event-Driven Features:**
```bash
/event-new Create UserRegistered event
/messenger-new Create WelcomeEmailHandler
/test-new Create event tests
```

---

## 🔧 DDEV Integration

All commands are optimized for DDEV workflows:

```bash
# Run commands inside DDEV
ddev exec bin/console [command]

# Common DDEV commands used with generated code
ddev exec bin/console make:migration
ddev exec bin/console doctrine:migrations:migrate
ddev exec bin/phpunit
ddev exec bin/console debug:container
ddev exec bin/console cache:clear
```

---

## 📚 Further Reading

- [Symfony Documentation](https://symfony.com/doc/current/index.html)
- [Doctrine ORM](https://www.doctrine-project.org/projects/orm.html)
- [Symfony UX](https://ux.symfony.com/)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [DDEV Documentation](https://ddev.readthedocs.io/)

---

## 🆘 Need Help?

Each command includes comprehensive examples and patterns. Run a command without arguments to see its full documentation, or check the individual command files in `.claude/commands/`.

**Command Files:**
- Development: `.claude/commands/misc/`
- API: `.claude/commands/api/`
- UI: `.claude/commands/ui/`
- Database: `.claude/commands/database/`
- Security: `.claude/commands/security/`

---

**Version:** 1.4.0
**Author:** Hempq
**License:** MIT
