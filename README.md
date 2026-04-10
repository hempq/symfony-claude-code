# Symfony Claude Code Plugin

Claude Code plugin for Symfony development. 26 skills, 11 AI agents, output styles, and DDEV shortcuts.

## Install

```bash
git clone https://github.com/hempq/symfony-claude-code.git

# In Claude Code:
/plugin marketplace add /path/to/symfony-claude-code
/plugin install symfony-claude-code
```

## Skills

### Development

| Skill | Description |
|-------|-------------|
| `/new-task` | Analyze task complexity and create implementation plans |
| `/service-new` | Create services with business logic |
| `/test-new` | Create PHPUnit tests (unit, integration, functional) |
| `/dto-new` | Create DTOs with validation |
| `/event-new` | Create events with listeners or subscribers |
| `/code-explain` | Explain PHP/Symfony code with diagrams |
| `/code-optimize` | Performance optimization |
| `/code-cleanup` | Refactoring and cleanup |
| `/feature-plan` | Plan feature implementation |
| `/lint` | PHP CS Fixer, PHPStan, Rector |
| `/docs-generate` | Generate documentation |

### API

| Skill | Description |
|-------|-------------|
| `/api-new` | Create API endpoints with validation |
| `/api-test` | Generate API functional tests |
| `/api-protect` | Add auth, rate limiting, CORS |

### UI

| Skill | Description |
|-------|-------------|
| `/controller-new` | Create controllers (CRUD, forms, dashboard) |
| `/form-new` | Create forms with validation |
| `/component-new` | Create Symfony UX components (Twig, Stimulus, Live) |
| `/page-new` | Create full pages with templates |

### Database

| Skill | Description |
|-------|-------------|
| `/entity-new` | Create Doctrine entities with validation |
| `/messenger-new` | Create Messenger handlers for async tasks |
| `/migration` | Generate and manage Doctrine migrations |
| `/fixture-new` | Create test data fixtures and factories |

### Symfony Internals

| Skill | Description |
|-------|-------------|
| `/command-new` | Create console commands |
| `/middleware-new` | Create Messenger middleware |
| `/twig-extension-new` | Create Twig filters, functions, and tests |
| `/voter-new` | Create Security Voters |

## Agents

Agents activate automatically based on context.

| Agent | When to use |
|-------|-------------|
| tech-stack-researcher | Technology comparisons, architecture decisions |
| system-architect | System design, component boundaries, scaling |
| backend-architect | API design, database schemas, caching |
| frontend-architect | Symfony UX, Stimulus, accessibility, Core Web Vitals |
| requirements-analyst | Turn vague ideas into specs and user stories |
| refactoring-expert | Break up large classes, apply SOLID, reduce debt |
| performance-engineer | Profile bottlenecks, optimize queries and memory |
| security-engineer | Audit code, threat model, OWASP compliance |
| technical-writer | API docs, READMEs, architecture decision records |
| learning-guide | Explain concepts, design patterns, Symfony internals |
| deep-research-agent | Multi-source research with evidence synthesis |

## Extras

**Output styles** — Switch response format with `/output-style`:
- `terse` — Code only, minimal prose
- `tutorial` — Step-by-step educational explanations

**Bin shortcuts** — Available in terminal while the plugin is active:

```
sf            → ddev exec bin/console
phptest       → ddev exec bin/phpunit
phpfix        → ddev exec vendor/bin/php-cs-fixer fix
phpstan       → ddev exec vendor/bin/phpstan analyse
```

**Hooks** — Automated reminders after editing PHP files and entities.

## Example Workflows

```bash
# Plan and scaffold a feature
/feature-plan Shopping cart with session persistence

# Entity → migration → form → controller → tests
/entity-new Product with name, price, category
/migration
/form-new ProductType for the Product entity
/controller-new Product CRUD controller
/test-new Functional tests for ProductController

# API with security
/api-new CRUD API for blog posts with pagination
/api-protect Add JWT authentication
/api-test
```

## Requirements

- Claude Code 2.1+
- PHP 8.2+ / Symfony 7.0+
- DDEV recommended

## Customization

Edit files in `skills/` and `agents/` to adjust any skill or agent behavior.

## License

MIT

## Author

Hempq — [github.com/hempq](https://github.com/hempq)
