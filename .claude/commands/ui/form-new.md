---
description: Create a Symfony form with validation
model: claude-sonnet-4-5
---

Create a Symfony form following best practices.

## Form Specification

$ARGUMENTS

## What to Generate

Create a form type with validation:

### 1. **Entity Form** (CRUD Forms)

**Files:**
- `src/Form/ProductType.php`
- Form tied to `Product` entity

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Product Name',
                'attr' => [
                    'placeholder' => 'Enter product name',
                ],
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Price',
                'currency' => 'EUR',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'rows' => 5,
                ],
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

**Entity with Validation:**
```php
<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank(message: 'Name is required')]
    #[Assert\Length(min: 3, max: 255)]
    private string $name;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private float $price;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 5000)]
    private ?string $description = null;

    // Getters and setters...
}
```

### 2. **DTO Form** (No Entity)

**Files:**
- `src/Form/ContactType.php`
- `src/Dto/ContactFormData.php`

**DTO:**
```php
<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class ContactFormData
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]
    public string $name = '';

    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email = '';

    #[Assert\NotBlank]
    #[Assert\Length(min: 10, max: 1000)]
    public string $message = '';
}
```

**Form:**
```php
<?php

declare(strict_types=1);

namespace App\Form;

use App\Dto\ContactFormData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Your Name',
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email Address',
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Message',
                'attr' => ['rows' => 10],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContactFormData::class,
        ]);
    }
}
```

### 3. **Complex Form** (With Relations)

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Category;
use App\Entity\Product;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('price', MoneyType::class, [
                'currency' => 'EUR',
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Active' => 'active',
                    'Inactive' => 'inactive',
                    'Draft' => 'draft',
                ],
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'placeholder' => 'Select a category',
            ])
            ->add('image', FileType::class, [
                'label' => 'Product Image (JPG/PNG)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => ['image/jpeg', 'image/png'],
                    ])
                ],
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

## Common Field Types

### **Text Fields**
```php
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;

$builder
    ->add('name', TextType::class)
    ->add('description', TextareaType::class)
    ->add('email', EmailType::class)
    ->add('password', PasswordType::class)
    ->add('website', UrlType::class)
;
```

### **Number Fields**
```php
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;

$builder
    ->add('quantity', IntegerType::class)
    ->add('weight', NumberType::class)
    ->add('price', MoneyType::class, [
        'currency' => 'EUR',
    ])
;
```

### **Date/Time Fields**
```php
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

$builder
    ->add('birthDate', DateType::class, [
        'widget' => 'single_text',
    ])
    ->add('startTime', TimeType::class, [
        'widget' => 'single_text',
    ])
    ->add('publishedAt', DateTimeType::class, [
        'widget' => 'single_text',
    ])
;
```

### **Choice Fields**
```php
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;

$builder
    ->add('status', ChoiceType::class, [
        'choices' => [
            'Active' => 'active',
            'Inactive' => 'inactive',
        ],
    ])
    ->add('featured', CheckboxType::class, [
        'required' => false,
    ])
    ->add('tags', ChoiceType::class, [
        'choices' => ['PHP' => 'php', 'Symfony' => 'symfony'],
        'multiple' => true,
        'expanded' => true, // Checkboxes instead of select
    ])
;
```

### **File Upload**
```php
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;

$builder
    ->add('attachment', FileType::class, [
        'label' => 'Upload File',
        'mapped' => false,
        'required' => false,
        'constraints' => [
            new File([
                'maxSize' => '5M',
                'mimeTypes' => ['application/pdf', 'image/*'],
            ])
        ],
    ])
;
```

### **Entity Relations**
```php
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

$builder
    ->add('category', EntityType::class, [
        'class' => Category::class,
        'choice_label' => 'name',
        'placeholder' => 'Choose a category',
    ])
    ->add('tags', EntityType::class, [
        'class' => Tag::class,
        'choice_label' => 'name',
        'multiple' => true,
        'expanded' => false, // Select dropdown
    ])
;
```

## Common Validation Constraints

```php
use Symfony\Component\Validator\Constraints as Assert;

#[Assert\NotBlank(message: 'This field is required')]
#[Assert\Length(min: 3, max: 255)]
#[Assert\Email]
#[Assert\Url]
#[Assert\Regex(pattern: '/^[a-z]+$/')]
#[Assert\Choice(choices: ['active', 'inactive'])]
#[Assert\Range(min: 0, max: 100)]
#[Assert\Positive]
#[Assert\PositiveOrZero]
#[Assert\GreaterThan(value: 0)]
#[Assert\LessThan(value: 100)]
#[Assert\Date]
#[Assert\DateTime]
#[Assert\Unique] // For collections
#[Assert\File(maxSize: '5M', mimeTypes: ['application/pdf'])]
#[Assert\Image(maxSize: '2M')]
```

## Controller Usage

### **Create**
```php
#[Route('/products/new', name: 'products_new')]
public function new(Request $request, EntityManagerInterface $em): Response
{
    $product = new Product();
    $form = $this->createForm(ProductType::class, $product);

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $em->persist($product);
        $em->flush();

        $this->addFlash('success', 'Product created!');

        return $this->redirectToRoute('products_index');
    }

    return $this->render('product/new.html.twig', [
        'form' => $form,
    ]);
}
```

### **Edit**
```php
#[Route('/products/{id}/edit', name: 'products_edit')]
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
```

## Template Usage

### **Basic Form**
```twig
{{ form_start(form) }}
    {{ form_widget(form) }}
    <button type="submit" class="btn btn-primary">Save</button>
{{ form_end(form) }}
```

### **Custom Layout**
```twig
{{ form_start(form) }}
    <div class="form-group">
        {{ form_label(form.name) }}
        {{ form_widget(form.name, {'attr': {'class': 'form-control'}}) }}
        {{ form_errors(form.name) }}
    </div>

    <div class="form-group">
        {{ form_label(form.price) }}
        {{ form_widget(form.price, {'attr': {'class': 'form-control'}}) }}
        {{ form_errors(form.price) }}
    </div>

    {{ form_rest(form) }}

    <button type="submit" class="btn btn-primary">Save</button>
    <a href="{{ path('products_index') }}" class="btn btn-secondary">Cancel</a>
{{ form_end(form) }}
```

### **Bootstrap Classes**
```twig
{{ form_start(form, {'attr': {'class': 'form'}}) }}
    {% for field in form %}
        <div class="mb-3">
            {{ form_label(field, null, {'label_attr': {'class': 'form-label'}}) }}
            {{ form_widget(field, {'attr': {'class': 'form-control'}}) }}
            {{ form_errors(field) }}
        </div>
    {% endfor %}

    <button type="submit" class="btn btn-primary">Submit</button>
{{ form_end(form) }}
```

## DDEV Commands

```bash
# Generate form
ddev exec bin/console make:form ProductType

# Generate form for entity
ddev exec bin/console make:form ProductType Product

# Generate entity with form
ddev exec bin/console make:entity Product

# Debug form
ddev exec bin/console debug:form ProductType
```

## Best Practices

**Form Design**
- One form type per entity/DTO
- Use strict types
- Clear labels and placeholders
- Group related fields

**Validation**
- Validate in entity/DTO, not form
- Use built-in constraints when possible
- Custom constraints for complex rules
- Clear error messages

**Security**
- CSRF protection automatic
- Sanitize file uploads
- Validate all input
- Use mapped: false for non-entity fields

**UX**
- Show validation errors clearly
- Use placeholders
- Add help text where needed
- Flash messages for success/error

Generate production-ready Symfony forms with validation that work seamlessly in DDEV environments.
