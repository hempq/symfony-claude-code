# Skill Reference

Quick reference for all 26 skills in this plugin.

```
/skill-name [describe what you need]
```

All skills generate Symfony 7+ / PHP 8.2+ code with DDEV integration.

## Development

| Skill | What it does | Patterns |
|-------|-------------|----------|
| `/new-task` | Plan implementation for a task | Complexity analysis, phased breakdown |
| `/service-new` | Create a Symfony service | Simple, API client, caching, events, file upload, email |
| `/test-new` | Create PHPUnit tests | Unit, integration, functional, repository, form, entity |
| `/dto-new` | Create DTOs with validation | Request, response, form, update, nested, collection |
| `/event-new` | Create events and listeners | Custom event, subscriber, Doctrine lifecycle, kernel events |
| `/code-explain` | Explain code with diagrams | Mermaid flowcharts, class diagrams, sequence diagrams |
| `/code-optimize` | Optimize performance | N+1 queries, caching, batch processing, generators |
| `/code-cleanup` | Refactor code | Extract method, SOLID, modern PHP patterns |
| `/feature-plan` | Plan a feature end-to-end | Architecture, phases, database, testing, rollout |
| `/lint` | Run linting tools | PHP CS Fixer, PHPStan, Rector (bundles template configs) |
| `/docs-generate` | Generate documentation | PHPDoc, API docs, README, component docs |

## API

| Skill | What it does | Patterns |
|-------|-------------|----------|
| `/api-new` | Create API endpoints | GET, POST, PATCH, DELETE, paginated collections |
| `/api-test` | Test API endpoints | Success, validation errors, auth, edge cases |
| `/api-protect` | Secure API endpoints | JWT, voters, rate limiting, CORS |

## UI

| Skill | What it does | Patterns |
|-------|-------------|----------|
| `/controller-new` | Create controllers | Simple, CRUD, API, form, dashboard |
| `/form-new` | Create forms | Entity form, DTO form, complex relations |
| `/component-new` | Create Symfony UX components | Twig, Stimulus, Live Components |
| `/page-new` | Create full pages | Static, list, detail, form, dashboard |

## Database

| Skill | What it does | Patterns |
|-------|-------------|----------|
| `/entity-new` | Create Doctrine entities | Relationships, enums, lifecycle callbacks, indexes |
| `/messenger-new` | Create async handlers | Commands, events, scheduled tasks, webhooks |
| `/migration` | Manage Doctrine migrations | Generate, run, rollback, data migrations |
| `/fixture-new` | Create test data | Fixtures, references, groups, Foundry factories |

## Symfony Internals

| Skill | What it does | Patterns |
|-------|-------------|----------|
| `/command-new` | Create console commands | Simple, interactive, progress bar, cron |
| `/middleware-new` | Create Messenger middleware | Logging, validation, transactions, audit |
| `/twig-extension-new` | Create Twig extensions | Filters, functions, tests, runtime (lazy) |

## Security

| Skill | What it does | Patterns |
|-------|-------------|----------|
| `/voter-new` | Create Security Voters | Owner-based, role-based, time-based, status-based |

## Agents

Agents activate automatically based on what you ask.

| Agent | Focus |
|-------|-------|
| tech-stack-researcher | Technology choices and comparisons |
| system-architect | System design, boundaries, scaling |
| backend-architect | APIs, database schemas, caching |
| frontend-architect | Symfony UX, Stimulus, accessibility |
| requirements-analyst | Specs, user stories, PRDs |
| refactoring-expert | SOLID, reduce complexity, clean code |
| performance-engineer | Profile bottlenecks, optimize queries |
| security-engineer | Audits, threat modeling, OWASP |
| technical-writer | API docs, guides, ADRs |
| learning-guide | Explain concepts and patterns |
| deep-research-agent | Multi-source research |

## Example Workflows

**Entity to CRUD:**
```
/entity-new Product with name, price, category
/migration
/form-new ProductType for the Product entity
/controller-new Product CRUD controller
/test-new Functional tests for ProductController
```

**API with security:**
```
/api-new CRUD API for blog posts with pagination
/api-protect Add JWT authentication
/api-test
```

**Event-driven feature:**
```
/event-new UserRegistered event with email listener
/messenger-new WelcomeEmailHandler
/test-new Event and handler tests
```

## DDEV Shortcuts

These bin scripts are available while the plugin is active:

```
sf            ddev exec bin/console
phptest       ddev exec bin/phpunit
phpfix        ddev exec vendor/bin/php-cs-fixer fix
phpstan       ddev exec vendor/bin/phpstan analyse
```

---

Version 2.0.0 | MIT | Hempq
