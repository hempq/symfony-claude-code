---
description: Create custom Twig extensions, filters, and functions
model: claude-sonnet-4-6
effort: high
context: fork
argument-hint: "[describe extension, e.g. 'price formatting filter with currency symbol and locale support']"
---

Create custom Twig extensions, filters, and functions following best practices.

## Extension Specification

$ARGUMENTS

## Existing Project Context

Existing Twig extensions:
!`find src/Twig -name "*.php" -type f 2>/dev/null | sort`

Before generating, check existing extensions to avoid duplicate filter/function names.

## What to Generate

Choose the approach based on requirements. Symfony 7.0+ supports both attribute-based and traditional approaches.

### 1. **Attribute-Based Filter** (Symfony 7.0+ Recommended)

**File:**
- `src/Twig/AppExtension.php`

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\Twig;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\Attribute\AsTwigFilter;

class AppExtension extends AbstractExtension
{
    #[AsTwigFilter('price')]
    public function formatPrice(float $amount, string $currency = 'EUR', string $locale = 'en'): string
    {
        $formatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);

        return $formatter->formatCurrency($amount, $currency);
    }

    #[AsTwigFilter('time_ago')]
    public function timeAgo(\DateTimeInterface $date): string
    {
        $now = new \DateTimeImmutable();
        $diff = $now->getTimestamp() - $date->getTimestamp();

        return match (true) {
            $diff < 60 => 'just now',
            $diff < 3600 => floor($diff / 60) . ' minutes ago',
            $diff < 86400 => floor($diff / 3600) . ' hours ago',
            $diff < 604800 => floor($diff / 86400) . ' days ago',
            $diff < 2592000 => floor($diff / 604800) . ' weeks ago',
            default => $date->format('M j, Y'),
        };
    }

    #[AsTwigFilter('truncate')]
    public function truncate(string $text, int $length = 100, string $suffix = '...'): string
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }

        return mb_substr($text, 0, $length - mb_strlen($suffix)) . $suffix;
    }

    #[AsTwigFilter('slug')]
    public function slugify(string $text): string
    {
        // Transliterate
        $text = transliterator_transliterate('Any-Latin; Latin-ASCII', $text);

        // Lowercase, replace non-alphanumeric with dashes
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        $text = trim($text, '-');

        return $text;
    }
}
```

**Template Usage:**
```twig
{# Price formatting #}
{{ product.price|price }}               {# $99.99 #}
{{ product.price|price('GBP', 'en') }}  {# 99.99 GBP #}
{{ product.price|price('EUR', 'de') }}  {# 99,99 EUR #}

{# Time ago #}
{{ post.createdAt|time_ago }}           {# 3 hours ago #}

{# Truncate text #}
{{ article.body|truncate(200) }}        {# First 200 chars... #}
{{ article.body|truncate(50, ' [more]') }}

{# Slugify #}
{{ "Hello World!"|slug }}               {# hello-world #}
```

### 2. **Attribute-Based Function** (Symfony 7.0+)

**File:**
- `src/Twig/AppExtension.php`

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\SettingsService;
use Twig\Attribute\AsTwigFunction;
use Twig\Extension\AbstractExtension;

class AppExtension extends AbstractExtension
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    #[AsTwigFunction('app_setting')]
    public function getSetting(string $key, mixed $default = null): mixed
    {
        return $this->settings->get($key, $default);
    }

    #[AsTwigFunction('app_version')]
    public function getAppVersion(): string
    {
        return $_ENV['APP_VERSION'] ?? '0.0.0';
    }

    #[AsTwigFunction('icon', options: ['is_safe' => ['html']])]
    public function renderIcon(string $name, string $class = ''): string
    {
        $classAttr = $class !== '' ? " class=\"{$class}\"" : '';

        return "<svg{$classAttr}><use xlink:href=\"#icon-{$name}\"></use></svg>";
    }

    #[AsTwigFunction('pluralize')]
    public function pluralize(int $count, string $singular, ?string $plural = null): string
    {
        if ($count === 1) {
            return "1 {$singular}";
        }

        $plural ??= $singular . 's';

        return "{$count} {$plural}";
    }
}
```

**Template Usage:**
```twig
{# App settings #}
{{ app_setting('site_name', 'My App') }}
{{ app_setting('maintenance_mode') ? 'Under maintenance' : '' }}

{# Version #}
<footer>v{{ app_version() }}</footer>

{# Icons (outputs raw HTML) #}
{{ icon('home') }}
{{ icon('user', 'icon-large') }}

{# Pluralization #}
{{ pluralize(product_count, 'product') }}    {# 1 product / 5 products #}
{{ pluralize(child_count, 'child', 'children') }}
```

### 3. **Attribute-Based Test** (Symfony 7.0+)

**File:**
- `src/Twig/AppExtension.php`

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\User;
use Twig\Attribute\AsTwigTest;
use Twig\Extension\AbstractExtension;

class AppExtension extends AbstractExtension
{
    #[AsTwigTest('admin')]
    public function isAdmin(User $user): bool
    {
        return in_array('ROLE_ADMIN', $user->getRoles(), true);
    }

    #[AsTwigTest('expired')]
    public function isExpired(\DateTimeInterface $date): bool
    {
        return $date < new \DateTimeImmutable();
    }

    #[AsTwigTest('empty_or_null')]
    public function isEmptyOrNull(mixed $value): bool
    {
        return $value === null || $value === '' || $value === [];
    }
}
```

**Template Usage:**
```twig
{# Test if user is admin #}
{% if user is admin %}
    <a href="{{ path('admin_dashboard') }}">Admin Panel</a>
{% endif %}

{# Test if subscription is expired #}
{% if subscription.expiresAt is expired %}
    <p class="alert-warning">Your subscription has expired.</p>
{% endif %}

{# Test if value is empty or null #}
{% if product.description is empty_or_null %}
    <p class="text-muted">No description available.</p>
{% endif %}
```

### 4. **Traditional AbstractExtension Approach** (All Symfony Versions)

**File:**
- `src/Twig/FormatExtension.php`

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

class FormatExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('bytes', $this->formatBytes(...)),
            new TwigFilter('phone', $this->formatPhone(...)),
            new TwigFilter('highlight', $this->highlight(...), ['is_safe' => ['html']]),
            new TwigFilter('json_pretty', $this->jsonPretty(...), ['is_safe' => ['html']]),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('lorem', $this->generateLorem(...)),
            new TwigFunction('class_list', $this->classList(...)),
        ];
    }

    public function getTests(): array
    {
        return [
            new TwigTest('numeric', $this->isNumeric(...)),
        ];
    }

    public function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;

        return round($bytes / (1024 ** $power), $precision) . ' ' . $units[(int) $power];
    }

    public function formatPhone(string $phone, string $country = 'US'): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        return match ($country) {
            'US' => sprintf('(%s) %s-%s',
                substr($digits, 0, 3),
                substr($digits, 3, 3),
                substr($digits, 6)
            ),
            'DE' => sprintf('+49 %s %s',
                substr($digits, 0, 3),
                substr($digits, 3)
            ),
            default => $phone,
        };
    }

    public function highlight(string $text, string $term): string
    {
        if ($term === '') {
            return $text;
        }

        $escaped = htmlspecialchars($term, ENT_QUOTES, 'UTF-8');

        return str_ireplace(
            $term,
            "<mark>{$escaped}</mark>",
            htmlspecialchars($text, ENT_QUOTES, 'UTF-8')
        );
    }

    public function jsonPretty(mixed $data): string
    {
        return '<pre><code>' . htmlspecialchars(
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            ENT_QUOTES,
            'UTF-8'
        ) . '</code></pre>';
    }

    public function generateLorem(int $words = 50): string
    {
        $lorem = 'Lorem ipsum dolor sit amet consectetur adipiscing elit sed do eiusmod tempor incididunt ut labore et dolore magna aliqua Ut enim ad minim veniam quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat';
        $parts = explode(' ', $lorem);

        $result = [];
        for ($i = 0; $i < $words; $i++) {
            $result[] = $parts[$i % count($parts)];
        }

        return implode(' ', $result) . '.';
    }

    public function classList(array $classes): string
    {
        // Filter out false/null values, keep string keys as conditional classes
        $result = [];

        foreach ($classes as $key => $value) {
            if (is_string($key)) {
                // Conditional: ['active' => true, 'disabled' => false]
                if ($value) {
                    $result[] = $key;
                }
            } else {
                // Always include: ['btn', 'btn-primary']
                $result[] = $value;
            }
        }

        return implode(' ', $result);
    }

    public function isNumeric(mixed $value): bool
    {
        return is_numeric($value);
    }
}
```

**Template Usage:**
```twig
{# File size formatting #}
{{ file.size|bytes }}            {# 1.5 MB #}
{{ file.size|bytes(0) }}         {# 2 MB #}

{# Phone formatting #}
{{ user.phone|phone }}           {# (555) 123-4567 #}
{{ user.phone|phone('DE') }}     {# +49 555 1234567 #}

{# Search term highlighting (outputs safe HTML) #}
{{ result.title|highlight(searchQuery) }}

{# JSON pretty print (outputs safe HTML) #}
{{ apiResponse|json_pretty }}

{# Lorem ipsum generator #}
{{ lorem(30) }}

{# Conditional CSS classes (like Vue/React class binding) #}
<div class="{{ class_list(['btn', 'btn-primary', {'active': isActive, 'disabled': isDisabled}]) }}">

{# Numeric test #}
{% if value is numeric %}
    <span>{{ value|number_format(2) }}</span>
{% endif %}
```

### 5. **Runtime Extension** (Lazy-Loaded with Dependencies)

For extensions that depend on services, use a Runtime class so the service is only instantiated when the filter/function is actually called in a template.

**Extension** (`src/Twig/MarkdownExtension.php`):
```php
<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class MarkdownExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('markdown', [MarkdownRuntime::class, 'convertToHtml'], ['is_safe' => ['html']]),
            new TwigFilter('markdown_excerpt', [MarkdownRuntime::class, 'excerpt']),
        ];
    }
}
```

**Runtime** (`src/Twig/MarkdownRuntime.php`):
```php
<?php

declare(strict_types=1);

namespace App\Twig;

use League\CommonMark\ConverterInterface;
use Twig\Extension\RuntimeExtensionInterface;

class MarkdownRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private readonly ConverterInterface $converter,
    ) {}

    public function convertToHtml(string $markdown): string
    {
        return $this->converter->convert($markdown)->getContent();
    }

    public function excerpt(string $markdown, int $length = 200): string
    {
        // Strip markdown syntax for plain text excerpt
        $text = strip_tags($this->convertToHtml($markdown));
        $text = preg_replace('/\s+/', ' ', trim($text));

        if (mb_strlen($text) <= $length) {
            return $text;
        }

        return mb_substr($text, 0, $length) . '...';
    }
}
```

**Template Usage:**
```twig
{# Full markdown rendering #}
{{ post.content|markdown }}

{# Excerpt for listing pages #}
{{ post.content|markdown_excerpt(150) }}
```

### 6. **Extension with HTML Output** (is_safe)

**File:**
- `src/Twig/BadgeExtension.php`

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Attribute\AsTwigFunction;
use Twig\Extension\AbstractExtension;

class BadgeExtension extends AbstractExtension
{
    #[AsTwigFunction('status_badge', options: ['is_safe' => ['html']])]
    public function statusBadge(string $status): string
    {
        $colorMap = [
            'active' => 'success',
            'pending' => 'warning',
            'inactive' => 'secondary',
            'banned' => 'danger',
            'draft' => 'info',
        ];

        $color = $colorMap[$status] ?? 'secondary';
        $label = htmlspecialchars(ucfirst($status), ENT_QUOTES, 'UTF-8');

        return "<span class=\"badge bg-{$color}\">{$label}</span>";
    }

    #[AsTwigFunction('progress_bar', options: ['is_safe' => ['html']])]
    public function progressBar(int $value, int $max = 100, string $color = 'primary'): string
    {
        $percent = $max > 0 ? min(100, round(($value / $max) * 100)) : 0;

        return <<<HTML
            <div class="progress">
                <div class="progress-bar bg-{$color}" role="progressbar"
                     style="width: {$percent}%"
                     aria-valuenow="{$value}" aria-valuemin="0" aria-valuemax="{$max}">
                    {$percent}%
                </div>
            </div>
            HTML;
    }

    #[AsTwigFunction('avatar', options: ['is_safe' => ['html']])]
    public function avatar(?string $name, int $size = 40): string
    {
        if ($name === null || $name === '') {
            $initials = '?';
        } else {
            $parts = explode(' ', $name);
            $initials = strtoupper(mb_substr($parts[0], 0, 1));
            if (count($parts) > 1) {
                $initials .= strtoupper(mb_substr(end($parts), 0, 1));
            }
        }

        $initials = htmlspecialchars($initials, ENT_QUOTES, 'UTF-8');

        return <<<HTML
            <div class="avatar" style="width: {$size}px; height: {$size}px; border-radius: 50%;
                 background: #6c757d; color: white; display: flex; align-items: center;
                 justify-content: center; font-size: {$size}px;">
                {$initials}
            </div>
            HTML;
    }
}
```

**Template Usage:**
```twig
{# Status badges #}
{{ status_badge(user.status) }}
{{ status_badge(order.status) }}

{# Progress bars #}
{{ progress_bar(75) }}
{{ progress_bar(task.completed, task.total, 'success') }}

{# User avatars #}
{{ avatar(user.name) }}
{{ avatar(user.name, 60) }}
```

## Global Template Variables

Add variables available in every template:

```php
<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class AppGlobalsExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly string $appName,
        private readonly string $appVersion,
        private readonly string $environment,
    ) {}

    public function getGlobals(): array
    {
        return [
            'app_name' => $this->appName,
            'app_version' => $this->appVersion,
            'app_env' => $this->environment,
        ];
    }
}
```

**Service configuration** (`config/services.yaml`):
```yaml
services:
    App\Twig\AppGlobalsExtension:
        arguments:
            $appName: '%env(APP_NAME)%'
            $appVersion: '%env(APP_VERSION)%'
            $environment: '%kernel.environment%'
```

**Template Usage:**
```twig
<title>{{ app_name }} - {{ page_title }}</title>
<footer>{{ app_name }} v{{ app_version }} ({{ app_env }})</footer>
```

## Testing Twig Extensions

### **Unit Test**

```php
<?php

declare(strict_types=1);

namespace App\Tests\Twig;

use App\Twig\AppExtension;
use PHPUnit\Framework\TestCase;

class AppExtensionTest extends TestCase
{
    private AppExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new AppExtension();
    }

    public function testFormatPrice(): void
    {
        $result = $this->extension->formatPrice(99.99, 'USD', 'en_US');

        $this->assertStringContainsString('99.99', $result);
        $this->assertStringContainsString('$', $result);
    }

    public function testTimeAgoMinutes(): void
    {
        $date = new \DateTimeImmutable('-30 minutes');
        $result = $this->extension->timeAgo($date);

        $this->assertSame('30 minutes ago', $result);
    }

    public function testTimeAgoHours(): void
    {
        $date = new \DateTimeImmutable('-3 hours');
        $result = $this->extension->timeAgo($date);

        $this->assertSame('3 hours ago', $result);
    }

    public function testTruncate(): void
    {
        $text = 'This is a long text that should be truncated';

        $this->assertSame('This is a lon...', $this->extension->truncate($text, 16));
        $this->assertSame($text, $this->extension->truncate($text, 100));
    }

    public function testSlugify(): void
    {
        $this->assertSame('hello-world', $this->extension->slugify('Hello World!'));
        $this->assertSame('my-great-post', $this->extension->slugify('My Great Post'));
    }
}
```

### **Integration Test (Rendering Templates)**

```php
<?php

declare(strict_types=1);

namespace App\Tests\Twig;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\Environment;

class TwigExtensionIntegrationTest extends KernelTestCase
{
    private Environment $twig;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->twig = static::getContainer()->get(Environment::class);
    }

    public function testPriceFilterInTemplate(): void
    {
        $template = $this->twig->createTemplate('{{ price|price }}');
        $result = $template->render(['price' => 49.99]);

        $this->assertStringContainsString('49.99', $result);
    }

    public function testStatusBadgeFunction(): void
    {
        $template = $this->twig->createTemplate('{{ status_badge(status) }}');
        $result = $template->render(['status' => 'active']);

        $this->assertStringContainsString('badge', $result);
        $this->assertStringContainsString('bg-success', $result);
        $this->assertStringContainsString('Active', $result);
    }
}
```

## DDEV Commands

```bash
# Clear Twig cache (required after adding/modifying extensions)
ddev exec bin/console cache:clear

# Debug: list all registered Twig extensions, filters, functions
ddev exec bin/console debug:twig

# Debug: show details for a specific filter
ddev exec bin/console debug:twig --filter=price

# Debug: show details for a specific function
ddev exec bin/console debug:twig --function=icon

# Check if extension is registered as a service
ddev exec bin/console debug:container AppExtension

# Run extension tests
ddev exec bin/phpunit tests/Twig/

# Lint all Twig templates
ddev exec bin/console lint:twig templates/
```

## Best Practices

**Extension Design**
- Keep extensions focused: one extension per domain (formatting, UI helpers, etc.)
- Use attribute-based registration (`#[AsTwigFilter]`, `#[AsTwigFunction]`, `#[AsTwigTest]`) on Symfony 7.0+
- Use Runtime extensions for filters/functions with heavy service dependencies
- Mark HTML-returning filters and functions with `is_safe => ['html']`
- Always escape user-provided content in HTML output with `htmlspecialchars`

**Naming**
- Use snake_case for filter and function names (`time_ago`, `format_price`)
- Prefix app-specific functions with `app_` to avoid collisions
- Keep names short but descriptive
- Follow Twig conventions (filters transform data, functions produce data, tests check conditions)

**Performance**
- Use Runtime extensions so services are only instantiated when the filter is called
- Avoid database queries in filters (pre-load data in the controller)
- Cache expensive computations if the same filter is called repeatedly
- Keep filters pure when possible (no side effects)

**Security**
- Never output raw user input without escaping
- Only use `is_safe` when you control the HTML output
- Sanitize all parameters used in HTML generation
- Be careful with `raw` filter usage in templates

**Testing**
- Unit test filter/function logic independently
- Integration test that the extension registers correctly
- Test edge cases (null, empty string, extreme values)
- Test HTML output for proper escaping

Generate production-ready Twig extensions that enhance your Symfony templates in DDEV environments.

## Next Steps

After creating Twig extensions, consider:
- `/test-new` — Write unit tests for filter and function logic
- `/component-new` — Create Twig components that use your extensions
