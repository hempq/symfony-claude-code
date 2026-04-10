---
description: Create a new Symfony UX component with Twig, Stimulus, or Live Components
model: claude-sonnet-4-6
effort: medium
---

Create a new Symfony UX component following best practices.

## Component Specification

$ARGUMENTS

## What to Generate

Choose the appropriate component type based on requirements:

### 1. **Twig Component** (Most Common)
Use for reusable UI with server-side logic.

**Files to create:**
- `src/Twig/Components/[Name].php` - Component class with props
- `templates/components/[Name].html.twig` - Template

**Example:**
```php
<?php
// src/Twig/Components/Alert.php
declare(strict_types=1);

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('alert')]
class Alert
{
    public string $message;
    public string $type = 'info'; // info, success, warning, error
}
```

```twig
{# templates/components/Alert.html.twig #}
<div class="alert alert-{{ type }}" {{ attributes }}>
    {{ message }}
</div>
```

**Usage:**
```twig
{{ component('alert', {
    message: 'User created successfully!',
    type: 'success'
}) }}
```

### 2. **Live Component** (Real-time/Interactive)
Use for components that need real-time updates without page reload.

**Files to create:**
- `src/Twig/Components/[Name].php` - Live component class
- `templates/components/[Name].html.twig` - Template with data-model

**Example:**
```php
<?php
// src/Twig/Components/SearchBox.php
declare(strict_types=1);

namespace App\Twig\Components;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('search_box')]
class SearchBox
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public string $query = '';

    public function getResults(): array
    {
        if (empty($this->query)) {
            return [];
        }

        // Search logic here
        return ['result1', 'result2'];
    }
}
```

```twig
{# templates/components/SearchBox.html.twig #}
<div {{ attributes }}>
    <input
        type="text"
        data-model="query"
        placeholder="Search..."
    >

    <ul>
        {% for result in this.results %}
            <li>{{ result }}</li>
        {% endfor %}
    </ul>
</div>
```

**Usage:**
```twig
{{ component('search_box') }}
```

### 3. **Stimulus Controller** (Client-side Behavior)
Use for JavaScript behavior without server interaction.

**File to create:**
- `assets/controllers/[name]_controller.ts` - Stimulus controller

**Example:**
```typescript
// assets/controllers/dropdown_controller.ts
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['menu'];
    static classes = ['open'];

    declare readonly menuTarget: HTMLElement;
    declare readonly openClass: string;

    toggle(): void {
        this.menuTarget.classList.toggle(this.openClass);
    }

    hide(): void {
        this.menuTarget.classList.remove(this.openClass);
    }
}
```

**Usage:**
```twig
<div data-controller="dropdown" class="dropdown">
    <button data-action="click->dropdown#toggle">Menu</button>

    <ul
        data-dropdown-target="menu"
        data-dropdown-open-class="show"
        class="dropdown-menu"
    >
        <li><a href="#">Item 1</a></li>
        <li><a href="#">Item 2</a></li>
    </ul>
</div>
```

## Component Patterns

### **Props with Defaults**
```php
#[AsTwigComponent('button')]
class Button
{
    public string $label;
    public string $variant = 'primary'; // default value
    public bool $disabled = false;
    public ?string $icon = null; // optional
}
```

### **Computed Properties**
```php
#[AsTwigComponent('product_card')]
class ProductCard
{
    public string $name;
    public float $price;
    public int $discount = 0;

    public function getDiscountedPrice(): float
    {
        return $this->price * (1 - $this->discount / 100);
    }

    public function hasDiscount(): bool
    {
        return $this->discount > 0;
    }
}
```

### **With Services**
```php
#[AsTwigComponent('user_badge')]
class UserBadge
{
    public int $userId;

    public function __construct(
        private readonly UserRepository $userRepository
    ) {}

    public function getUser(): User
    {
        return $this->userRepository->find($this->userId);
    }
}
```

### **Live Component Actions**
```php
#[AsLiveComponent('counter')]
class Counter
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public int $count = 0;

    #[LiveAction]
    public function increment(): void
    {
        $this->count++;
    }

    #[LiveAction]
    public function decrement(): void
    {
        $this->count--;
    }
}
```

```twig
<div {{ attributes }}>
    <p>Count: {{ count }}</p>
    <button data-action="live#action" data-action-name="increment">+</button>
    <button data-action="live#action" data-action-name="decrement">-</button>
</div>
```

## Installation Requirements

```bash
# Twig Components
ddev composer require symfony/ux-twig-component

# Live Components
ddev composer require symfony/ux-live-component

# Stimulus
ddev exec npm install @hotwired/stimulus
```

## DDEV Commands

```bash
# Create Twig Component (requires MakerBundle)
ddev exec bin/console make:twig-component ButtonComponent

# Install Symfony UX packages
ddev composer require symfony/ux-turbo
ddev composer require symfony/ux-live-component

# Build assets
ddev exec npm run dev
ddev exec npm run watch

# Clear cache
ddev exec bin/console cache:clear
```

## Best Practices

### **Component Design**
- Keep components small and focused (single responsibility)
- Use props for configuration, not hardcoded values
- Default values for optional props
- Type-hint all properties (strict types)

### **Naming**
- PascalCase for PHP classes: `AlertMessage`
- snake_case for component names: `alert_message`
- kebab-case for Stimulus controllers: `alert-message`

### **When to Use What**
- **Twig Component**: Reusable UI, server-rendered
- **Live Component**: Real-time updates, forms, search
- **Stimulus**: Client-side interactions, animations, toggles
- **Twig Component + Stimulus**: Best of both worlds

### **Performance**
- Twig Components are cached in production
- Live Components use AJAX (not WebSockets)
- Stimulus is lightweight (13KB)
- Use lazy loading for heavy components

Generate production-ready Symfony UX components that work seamlessly in DDEV environments.

## Next Steps

After creating a component, consider:
- `/page-new` — Create a page that uses this component
- `/test-new` — Write tests for component logic
