# Hempq's Claude Code Setup for Symfony

My personal Claude Code configuration for productive Symfony development. This plugin provides **21 slash commands** and **11 specialized AI agents** to supercharge your development workflow with Symfony, Symfony UX, and DDEV.

## Quick Install

```bash
git clone https://github.com/hempq/symfony-claude-code.git
cd symfony-claude-code

# Add as local marketplace
/plugin marketplace add /path/to/symfony-claude-code

# Install plugin
/plugin install symfony-claude-code
```

## What's Inside

### 📋 Development Commands (11)

- `/new-task` - Analyze task complexity and create implementation plans
- `/service-new` - Create Symfony services with business logic
- `/test-new` - Create PHPUnit tests (unit, integration, functional)
- `/dto-new` - Create DTOs with validation
- `/event-new` - Create events with listeners or subscribers
- `/code-explain` - Generate detailed explanations
- `/code-optimize` - Performance optimization
- `/code-cleanup` - Refactoring and cleanup
- `/feature-plan` - Feature implementation planning
- `/lint` - Linting and fixes
- `/docs-generate` - Documentation generation

### 🔌 API Commands (3)

- `/api-new` - Create new API endpoints
- `/api-test` - Test API endpoints
- `/api-protect` - Add protection & validation

### 🎨 UI Commands (4)

- `/controller-new` - Create simple Symfony controllers (CRUD, API, Forms, Dashboard)
- `/form-new` - Create Symfony forms with validation
- `/component-new` - Create Symfony UX components (Twig, Stimulus, LiveComponents)
- `/page-new` - Create full pages with controllers and templates

### 💾 Database Commands (2)

- `/entity-new` - Create Doctrine entities with validation and migrations
- `/messenger-new` - Create Symfony Messenger handlers for async tasks

### 🔒 Security Commands (1)

- `/voter-new` - Create Security Voters for authorization

### 🤖 Specialized AI Agents (11)

**Architecture & Planning**
- **tech-stack-researcher** - Technology choice recommendations with trade-offs
- **system-architect** - Scalable system architecture design
- **backend-architect** - Backend systems with data integrity & security
- **frontend-architect** - Performant, accessible UI architecture
- **requirements-analyst** - Transform ideas into concrete specifications

**Code Quality & Performance**
- **refactoring-expert** - Systematic refactoring and clean code
- **performance-engineer** - Measurement-driven optimization
- **security-engineer** - Vulnerability identification and security standards

**Documentation & Research**
- **technical-writer** - Clear, comprehensive documentation
- **learning-guide** - Teaching programming concepts progressively
- **deep-research-agent** - Comprehensive research with adaptive strategies

## Installation

```bash
git clone https://github.com/hempq/symfony-claude-code.git
cd symfony-claude-code

# Add as local marketplace
/plugin marketplace add /path/to/symfony-claude-code

# Install plugin
/plugin install symfony-claude-code
```



## Best For

- Symfony developers
- PHP projects with DDEV
- Doctrine ORM users
- Symfony UX developers
- Full-stack PHP engineers

## Command Aliases

For faster usage, several commands have shorter aliases:

- `/api` → `/api-new`
- `/entity` → `/entity-new`
- `/test` → `/test-new`
- `/svc` → `/service-new`
- `/ctrl` → `/controller-new`

## Usage Examples

### Planning a Feature

```bash
/feature-plan
# Then describe your feature idea
```

### Creating a Controller

```bash
/controller-new
# Claude will create a simple controller with routes and templates
```

### Creating an API

```bash
/api-new
# Claude will scaffold a complete Symfony API controller with validation and error handling
```

### Creating a Form

```bash
/form-new
# Claude will generate a Symfony form with validation constraints
```

### Creating a Service

```bash
/service-new
# Claude will create a service with business logic and dependencies
```

### Research Tech Choices

Just ask Claude questions like:
- "Should I use WebSockets or SSE?"
- "How should I structure this database?"
- "What's the best library for X?"

The tech-stack-researcher agent automatically activates and provides detailed, researched answers.

## Philosophy

This setup emphasizes:
- **Type Safety**: Strict PHP types and Doctrine annotations
- **Best Practices**: Follows modern Symfony 7+ patterns
- **Productivity**: Reduces repetitive scaffolding
- **Research**: AI-powered tech decisions with evidence
- **DDEV Integration**: Optimized for DDEV container workflows

## Requirements

- Claude Code 2.0.13+
- PHP 8.2+
- Symfony 7.0+
- DDEV (recommended for local development)
- Works with any PHP project (optimized for Symfony + Doctrine)

## Customization

After installation, you can customize any command by editing files in `.claude/commands/` and `.claude/agents/`.

## Contributing

Feel free to:
- Fork and customize for your needs
- Submit issues or suggestions
- Share your improvements

## License

MIT - Use freely in your projects

## Author

Created by Hempq

---

**Note**: This is my personal setup that I've refined over time. Commands are optimized for Symfony + DDEV workflows but work great with any modern PHP stack.
