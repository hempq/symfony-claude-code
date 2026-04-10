---
description: Plan feature implementation with technical specifications
model: claude-sonnet-4-6
effort: medium
---

Create a detailed implementation plan for the following Symfony feature.

## Feature Description

$ARGUMENTS

## Planning Framework for Symfony Developers

### 1. **Feature Breakdown**

Analyze and break down into:
- User stories
- Technical requirements
- Dependencies
- Edge cases
- Success criteria

### 2. **Technical Specification**

**Architecture**
- Where does this fit in the codebase?
- Which controllers/services affected?
- New vs modified files
- Database schema changes
- API endpoints needed

**Technology Choices**
- Symfony bundles needed
- Composer packages required
- Why each choice?
- Alternatives considered
- Trade-offs

**Data Flow**
```
User Action → Controller → Service → Repository → Database → Response
```

### 3. **Implementation Steps**

Break into logical, sequential tasks:

1. **Setup** - Dependencies, configuration
2. **Database** - Entities, migrations, fixtures
3. **Backend** - Services, repositories, validation
4. **Controllers** - HTTP endpoints, forms
5. **Frontend** - Twig templates, Symfony UX components
6. **Integration** - Connect pieces
7. **Testing** - Unit, functional, integration
8. **Polish** - Error handling, flash messages, UX

### 4. **Risk Assessment**

Identify potential issues:
- **Technical Risks** - Complexity, new territory
- **Time Risks** - Underestimated tasks
- **Dependency Risks** - Third-party packages, APIs
- **Data Risks** - Migrations, backward compatibility

### 5. **Estimation**

Realistic time estimates:
- Small task: 1-2 hours
- Medium task: Half day
- Large task: 1-2 days
- Complex task: 3-5 days

**Rule of thumb**: Double your initial estimate for solo development.

### 6. **Success Criteria**

Define "done":
- ✅ Feature works as specified
- ✅ Tests pass (PHPUnit)
- ✅ No console errors or warnings
- ✅ Forms validated
- ✅ Error handling implemented
- ✅ Flash messages for user feedback
- ✅ Documentation updated

## Output Format

### 1. **Feature Overview**
- What problem does this solve?
- Who is it for?
- Key functionality

### 2. **Technical Design**

```
┌─────────────┐      ┌──────────────┐      ┌──────────────┐
│  Controller │─────▶│   Service    │─────▶│  Repository  │
└─────────────┘      └──────────────┘      └──────────────┘
       │                    │                      │
       │                    │                      ▼
       │                    │               ┌──────────────┐
       │                    └──────────────▶│   Database   │
       ▼                                    └──────────────┘
┌─────────────┐
│ Twig/Forms  │
└─────────────┘
```

- Entity structure
- Service responsibilities
- Controller actions
- Template organization

### 3. **Implementation Plan**

**Phase 1: Foundation** (Day 1)
- [ ] Create database entities
- [ ] Generate migrations
- [ ] Create repositories

**Phase 2: Business Logic** (Day 2)
- [ ] Create service classes
- [ ] Implement validation
- [ ] Add error handling

**Phase 3: Controllers & Templates** (Day 3)
- [ ] Create controllers
- [ ] Build Twig templates
- [ ] Create forms (if needed)

**Phase 4: Testing & Polish** (Day 4)
- [ ] Write PHPUnit tests
- [ ] Add flash messages
- [ ] Handle edge cases

### 4. **File Changes**

**New Files**
```
src/
├── Controller/FeatureController.php
├── Entity/Feature.php
├── Repository/FeatureRepository.php
├── Service/FeatureService.php
├── Form/FeatureType.php (if forms needed)
└── Dto/CreateFeatureRequest.php (for APIs)

templates/
└── feature/
    ├── index.html.twig
    ├── show.html.twig
    └── _form.html.twig

tests/
└── Controller/FeatureControllerTest.php

migrations/
└── VersionYYYYMMDDHHMMSS.php
```

**Modified Files**
```
config/routes.yaml (if not using attributes)
templates/base.html.twig (add navigation)
```

### 5. **Dependencies**

**Composer packages to install**
```bash
ddev composer require package/name
ddev composer require --dev package/dev-tool
```

**Symfony bundles**
```bash
# Example: If we need pagination
ddev composer require knplabs/knp-paginator-bundle

# Example: If we need forms
ddev composer require symfony/form
```

**Environment variables**
```bash
# .env.local
FEATURE_API_KEY=your_key_here
FEATURE_ENABLED=true
```

### 6. **Database Changes**

**Entity Design**
```php
<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FeatureRepository::class)]
#[ORM\Table(name: 'features')]
class Feature
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $name;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    // Getters and setters...
}
```

**Migration Commands**
```bash
# Generate migration
ddev exec bin/console make:migration

# Review migration file
cat migrations/VersionYYYYMMDDHHMMSS.php

# Run migration
ddev exec bin/console doctrine:migrations:migrate
```

### 7. **Testing Strategy**

**Unit Tests** - Test services in isolation
```php
class FeatureServiceTest extends TestCase
{
    public function testCreateFeature(): void
    {
        $repository = $this->createMock(FeatureRepository::class);
        $service = new FeatureService($repository);

        $result = $service->createFeature('Test Feature');

        $this->assertInstanceOf(Feature::class, $result);
    }
}
```

**Functional Tests** - Test controllers
```php
class FeatureControllerTest extends WebTestCase
{
    public function testListFeatures(): void
    {
        $client = static::createClient();
        $client->request('GET', '/features');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Features');
    }
}
```

**Integration Tests** - Test full flow
```bash
# Run all tests
ddev exec bin/phpunit

# Run specific test
ddev exec bin/phpunit tests/Controller/FeatureControllerTest.php
```

### 8. **Configuration**

**Services** (`config/services.yaml`)
```yaml
services:
    App\Service\FeatureService:
        arguments:
            $cacheEnabled: '%env(bool:FEATURE_CACHE_ENABLED)%'
```

**Routes** (if not using attributes)
```yaml
# config/routes.yaml
feature_index:
    path: /features
    controller: App\Controller\FeatureController::index
    methods: [GET]
```

**Parameters** (`config/packages/parameters.yaml`)
```yaml
parameters:
    feature.items_per_page: 20
    feature.cache_ttl: 3600
```

### 9. **Rollout Plan**

**Development**
1. Implement on feature branch
2. Test locally with DDEV
3. Create pull request

**Staging**
```bash
# Deploy to staging
git push staging feature-branch

# Run migrations
bin/console doctrine:migrations:migrate --no-interaction

# Clear cache
bin/console cache:clear --env=prod
```

**Production**
1. Merge to main
2. Run migrations
3. Clear cache
4. Monitor error logs
5. Check metrics

**Rollback Plan**
```bash
# Rollback migration if needed
bin/console doctrine:migrations:execute --down VERSION

# Revert code
git revert <commit-hash>
```

### 10. **Monitoring & Metrics**

**What to Monitor**
- Response times (Symfony Profiler)
- Error rates (logs)
- Database query performance
- User adoption

**DDEV Monitoring**
```bash
# View logs
ddev logs -f

# Check database
ddev mysql
SELECT * FROM features ORDER BY created_at DESC LIMIT 10;

# Profile performance
ddev blackfire curl https://myproject.ddev.site/features
```

### 11. **Example: User Registration Feature**

**Feature:** Add user registration with email verification

**Phase 1: Database** (2 hours)
- [ ] Create User entity with email, password, verified fields
- [ ] Add EmailVerification entity for tokens
- [ ] Generate and run migrations
- [ ] Create fixtures for testing

**Phase 2: Services** (4 hours)
- [ ] UserService for creating users
- [ ] EmailVerificationService for sending tokens
- [ ] PasswordHasher integration
- [ ] Validation (unique email, strong password)

**Phase 3: Controllers** (3 hours)
- [ ] RegistrationController with register() action
- [ ] EmailVerificationController with verify() action
- [ ] Handle success/error cases
- [ ] Flash messages

**Phase 4: Templates** (2 hours)
- [ ] Registration form with Twig
- [ ] Email verification template
- [ ] Success/error pages

**Phase 5: Testing** (3 hours)
- [ ] Unit tests for services
- [ ] Functional tests for controllers
- [ ] Test email sending (use MailCatcher in DDEV)
- [ ] Test validation rules

**Total Estimate:** 14 hours (~2 days)

**Dependencies:**
- symfony/mailer (email sending)
- symfony/validator (validation)
- symfony/security-bundle (password hashing)

**Risks:**
- Email deliverability in production
- Token expiration handling
- Rate limiting registration attempts

## Next Steps

1. **Review plan with stakeholders** (if applicable)
2. **Set up development environment** (`ddev start`)
3. **Create feature branch** (`git checkout -b feature/name`)
4. **Start with Phase 1** (database/entities)
5. **Test incrementally** after each phase
6. **Deploy to staging** for final testing
7. **Deploy to production** with monitoring

Provide a clear, actionable plan that a Symfony developer can follow step-by-step with DDEV.
