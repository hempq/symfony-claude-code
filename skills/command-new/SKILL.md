---
description: Create console commands with arguments, options, progress bars, and cron scheduling
model: claude-sonnet-4-6
effort: high
context: fork
argument-hint: "[describe command, e.g. 'import products from CSV file with validation and progress bar']"
---

Create a Symfony Console command following best practices.

## Command Specification

$ARGUMENTS

## Existing Project Context

Existing commands:
!`find src/Command -name "*.php" -type f 2>/dev/null | sort`

Before generating, check existing commands for naming prefix (e.g., app:*) and output style.

## What to Generate

Choose the command pattern based on requirements:

### 1. **Simple Command**

**File:**
- `src/Command/GreetCommand.php`

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:greet',
    description: 'Greet a user by name',
)]
class GreetCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'The name to greet')
            ->addOption('uppercase', 'u', InputOption::VALUE_NONE, 'Output greeting in uppercase')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $name = $input->getArgument('name');
        $greeting = "Hello, {$name}!";

        if ($input->getOption('uppercase')) {
            $greeting = strtoupper($greeting);
        }

        $io->success($greeting);

        return Command::SUCCESS;
    }
}
```

### 2. **Command with Service Dependencies**

**File:**
- `src/Command/CleanupUsersCommand.php`

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:cleanup-users',
    description: 'Remove unverified users older than a given number of days',
)]
class CleanupUsersCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('days', 'd', InputOption::VALUE_REQUIRED, 'Remove users older than N days', '30')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be deleted without actually deleting')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $days = (int) $input->getOption('days');
        $dryRun = $input->getOption('dry-run');

        $cutoffDate = new \DateTimeImmutable("-{$days} days");

        $io->title('User Cleanup');
        $io->text("Removing unverified users created before {$cutoffDate->format('Y-m-d')}");

        $users = $this->userRepository->findUnverifiedBefore($cutoffDate);
        $count = count($users);

        if ($count === 0) {
            $io->info('No users to clean up.');
            return Command::SUCCESS;
        }

        $io->text("Found {$count} user(s) to remove.");

        if ($dryRun) {
            $io->warning('Dry run mode - no users will be deleted.');
            $io->table(
                ['ID', 'Email', 'Created At'],
                array_map(fn ($u) => [$u->getId(), $u->getEmail(), $u->getCreatedAt()->format('Y-m-d')], $users)
            );
            return Command::SUCCESS;
        }

        foreach ($users as $user) {
            $this->em->remove($user);
        }

        $this->em->flush();

        $this->logger->info('Cleaned up unverified users', [
            'count' => $count,
            'olderThanDays' => $days,
        ]);

        $io->success("Removed {$count} unverified user(s).");

        return Command::SUCCESS;
    }
}
```

### 3. **Import Command with Progress Bar**

**File:**
- `src/Command/ImportProductsCommand.php`

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-products',
    description: 'Import products from a CSV file',
)]
class ImportProductsCommand extends Command
{
    private const BATCH_SIZE = 100;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'Path to the CSV file')
            ->addOption('delimiter', null, InputOption::VALUE_REQUIRED, 'CSV delimiter', ',')
            ->addOption('skip-header', null, InputOption::VALUE_NONE, 'Skip the first row (header)')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Validate without importing')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filePath = $input->getArgument('file');
        $delimiter = $input->getOption('delimiter');
        $skipHeader = $input->getOption('skip-header');
        $dryRun = $input->getOption('dry-run');

        // Validate file
        if (!file_exists($filePath)) {
            $io->error("File not found: {$filePath}");
            return Command::FAILURE;
        }

        $io->title('Product Import');

        // Count lines for progress bar
        $lineCount = $this->countLines($filePath);
        if ($skipHeader) {
            $lineCount--;
        }

        $io->text("Processing {$lineCount} records from {$filePath}");

        if ($dryRun) {
            $io->warning('Dry run mode - no data will be imported.');
        }

        // Open file and process
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            $io->error("Cannot open file: {$filePath}");
            return Command::FAILURE;
        }

        if ($skipHeader) {
            fgetcsv($handle, 0, $delimiter);
        }

        $progressBar = $io->createProgressBar($lineCount);
        $progressBar->setFormat('debug');
        $progressBar->start();

        $imported = 0;
        $errors = [];
        $batch = 0;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $progressBar->advance();

            try {
                // Validate row
                if (count($row) < 3) {
                    $errors[] = "Row " . ($imported + count($errors) + 1) . ": insufficient columns";
                    continue;
                }

                [$name, $price, $sku] = $row;

                if (empty($name) || !is_numeric($price)) {
                    $errors[] = "Row " . ($imported + count($errors) + 1) . ": invalid data (name={$name}, price={$price})";
                    continue;
                }

                if (!$dryRun) {
                    $product = new Product();
                    $product->setName(trim($name));
                    $product->setPrice((float) $price);
                    $product->setSku(trim($sku));
                    $product->setCreatedAt(new \DateTimeImmutable());

                    $this->em->persist($product);
                    $batch++;

                    // Flush in batches for memory efficiency
                    if ($batch >= self::BATCH_SIZE) {
                        $this->em->flush();
                        $this->em->clear();
                        $batch = 0;
                    }
                }

                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Row " . ($imported + count($errors) + 1) . ": " . $e->getMessage();
            }
        }

        // Flush remaining
        if (!$dryRun && $batch > 0) {
            $this->em->flush();
            $this->em->clear();
        }

        fclose($handle);
        $progressBar->finish();
        $io->newLine(2);

        // Summary
        $io->section('Import Summary');
        $io->definitionList(
            ['Imported' => $imported],
            ['Errors' => count($errors)],
            ['Mode' => $dryRun ? 'Dry Run' : 'Live'],
        );

        if (!empty($errors)) {
            $io->warning('Errors encountered:');
            $io->listing(array_slice($errors, 0, 20));

            if (count($errors) > 20) {
                $io->text(sprintf('... and %d more errors.', count($errors) - 20));
            }
        }

        $this->logger->info('Product import completed', [
            'imported' => $imported,
            'errors' => count($errors),
            'file' => $filePath,
        ]);

        if (count($errors) > 0 && $imported === 0) {
            return Command::FAILURE;
        }

        $io->success("Import complete: {$imported} products imported.");

        return Command::SUCCESS;
    }

    private function countLines(string $filePath): int
    {
        $count = 0;
        $handle = fopen($filePath, 'r');

        while (fgets($handle) !== false) {
            $count++;
        }

        fclose($handle);

        return $count;
    }
}
```

### 4. **Interactive Command**

**File:**
- `src/Command/CreateUserCommand.php`

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'app:create-user',
    description: 'Create a new user interactively',
)]
class CreateUserCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface $validator,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Create New User');

        // Interactive prompts
        $email = $io->ask('Email address', null, function (?string $value): string {
            if (empty($value) || !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                throw new \RuntimeException('Please provide a valid email address.');
            }
            return $value;
        });

        $name = $io->ask('Full name', null, function (?string $value): string {
            if (empty($value)) {
                throw new \RuntimeException('Name cannot be empty.');
            }
            return $value;
        });

        $password = $io->askHidden('Password (hidden input)', function (?string $value): string {
            if (empty($value) || strlen($value) < 8) {
                throw new \RuntimeException('Password must be at least 8 characters.');
            }
            return $value;
        });

        $role = $io->choice('Role', ['ROLE_USER', 'ROLE_EDITOR', 'ROLE_ADMIN'], 'ROLE_USER');

        // Confirm
        $io->section('Review');
        $io->table(
            ['Field', 'Value'],
            [
                ['Email', $email],
                ['Name', $name],
                ['Role', $role],
            ]
        );

        if (!$io->confirm('Create this user?', true)) {
            $io->warning('Cancelled.');
            return Command::SUCCESS;
        }

        // Create user
        $user = new User();
        $user->setEmail($email);
        $user->setName($name);
        $user->setRoles([$role]);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setCreatedAt(new \DateTimeImmutable());

        // Validate
        $violations = $this->validator->validate($user);

        if (count($violations) > 0) {
            $io->error('Validation errors:');

            foreach ($violations as $violation) {
                $io->text("  - {$violation->getPropertyPath()}: {$violation->getMessage()}");
            }

            return Command::FAILURE;
        }

        $this->em->persist($user);
        $this->em->flush();

        $io->success("User created with ID: {$user->getId()}");

        return Command::SUCCESS;
    }
}
```

### 5. **Command with Table Output and Filtering**

**File:**
- `src/Command/ListOrdersCommand.php`

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\OrderRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:list-orders',
    description: 'List orders with optional status filter',
)]
class ListOrdersCommand extends Command
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('status', 's', InputOption::VALUE_REQUIRED, 'Filter by status (pending, paid, shipped, cancelled)')
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Maximum number of orders to display', '25')
            ->addOption('format', 'f', InputOption::VALUE_REQUIRED, 'Output format (table, json)', 'table')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $status = $input->getOption('status');
        $limit = (int) $input->getOption('limit');
        $format = $input->getOption('format');

        $criteria = [];
        if ($status !== null) {
            $criteria['status'] = $status;
        }

        $orders = $this->orderRepository->findBy($criteria, ['createdAt' => 'DESC'], $limit);

        if (empty($orders)) {
            $io->info('No orders found.');
            return Command::SUCCESS;
        }

        if ($format === 'json') {
            $data = array_map(fn ($order) => [
                'id' => $order->getId(),
                'customer' => $order->getCustomer()->getEmail(),
                'total' => $order->getTotal(),
                'status' => $order->getStatus(),
                'createdAt' => $order->getCreatedAt()->format('c'),
            ], $orders);

            $output->writeln(json_encode($data, JSON_PRETTY_PRINT));

            return Command::SUCCESS;
        }

        $io->title('Orders');

        $rows = array_map(fn ($order) => [
            $order->getId(),
            $order->getCustomer()->getEmail(),
            number_format($order->getTotal(), 2) . ' EUR',
            $order->getStatus(),
            $order->getCreatedAt()->format('Y-m-d H:i'),
        ], $orders);

        $io->table(
            ['ID', 'Customer', 'Total', 'Status', 'Created At'],
            $rows
        );

        $io->text(sprintf('Showing %d of %d total orders.',
            count($orders),
            $this->orderRepository->count($criteria)
        ));

        return Command::SUCCESS;
    }
}
```

### 6. **Scheduled Command** (with Symfony Scheduler)

**File:**
- `src/Command/SendDailyReportCommand.php`

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\ReportService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

#[AsCommand(
    name: 'app:send-daily-report',
    description: 'Generate and send the daily summary report',
)]
#[AsCronTask('0 8 * * *', timezone: 'Europe/Berlin')]
class SendDailyReportCommand extends Command
{
    public function __construct(
        private readonly ReportService $reportService,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Daily Report');

        try {
            $yesterday = new \DateTimeImmutable('yesterday');
            $today = new \DateTimeImmutable('today');

            $report = $this->reportService->generateDailyReport($yesterday, $today);

            $io->definitionList(
                ['Date' => $yesterday->format('Y-m-d')],
                ['New Orders' => $report['orderCount']],
                ['Revenue' => number_format($report['revenue'], 2) . ' EUR'],
                ['New Users' => $report['newUsers']],
            );

            $this->reportService->sendReportEmail($report);

            $this->logger->info('Daily report sent', [
                'date' => $yesterday->format('Y-m-d'),
                'orderCount' => $report['orderCount'],
            ]);

            $io->success('Daily report generated and sent.');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->logger->error('Failed to generate daily report', [
                'error' => $e->getMessage(),
            ]);

            $io->error('Failed: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }
}
```

## Common Command Patterns

### **Arguments and Options Reference**

```php
protected function configure(): void
{
    $this
        // Required argument
        ->addArgument('name', InputArgument::REQUIRED, 'The user name')

        // Optional argument with default
        ->addArgument('greeting', InputArgument::OPTIONAL, 'The greeting', 'Hello')

        // Array argument (captures all remaining args)
        ->addArgument('emails', InputArgument::IS_ARRAY, 'One or more emails')

        // Boolean flag (--verbose or -v)
        ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Do not persist changes')

        // Option with required value (--format=json)
        ->addOption('format', 'f', InputOption::VALUE_REQUIRED, 'Output format', 'table')

        // Option with optional value (--color or --color=red)
        ->addOption('color', 'c', InputOption::VALUE_OPTIONAL, 'Color', 'blue')

        // Option that accepts array (--tag=a --tag=b)
        ->addOption('tag', 't', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Tags')
    ;
}
```

### **SymfonyStyle Output Helpers**

```php
protected function execute(InputInterface $input, OutputInterface $output): int
{
    $io = new SymfonyStyle($input, $output);

    // Headings
    $io->title('Main Title');
    $io->section('Section Heading');

    // Messages
    $io->success('Operation completed.');
    $io->error('Something went wrong.');
    $io->warning('Proceed with caution.');
    $io->info('Informational message.');
    $io->note('Note to the user.');
    $io->caution('Important caution.');

    // Lists
    $io->listing(['Item 1', 'Item 2', 'Item 3']);

    // Tables
    $io->table(
        ['Name', 'Email', 'Role'],
        [
            ['Alice', 'alice@example.com', 'Admin'],
            ['Bob', 'bob@example.com', 'User'],
        ]
    );

    // Horizontal table
    $io->horizontalTable(
        ['Name', 'Email', 'Role'],
        [
            ['Alice', 'alice@example.com', 'Admin'],
        ]
    );

    // Definition list
    $io->definitionList(
        ['Name' => 'Alice'],
        ['Email' => 'alice@example.com'],
        ['Role' => 'Admin'],
    );

    // Text output
    $io->text('Simple text line.');
    $io->text(['Line 1', 'Line 2']);
    $io->newLine(2);

    return Command::SUCCESS;
}
```

### **Progress Bar Patterns**

```php
// Simple progress bar
$progressBar = $io->createProgressBar(count($items));
$progressBar->start();

foreach ($items as $item) {
    // process...
    $progressBar->advance();
}

$progressBar->finish();
$io->newLine();

// Progress bar with custom format
$progressBar = $io->createProgressBar(count($items));
$progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
$progressBar->start();

// Iterate with progress (shorthand)
$io->progressStart(count($items));

foreach ($items as $item) {
    // process...
    $io->progressAdvance();
}

$io->progressFinish();
```

## Testing Commands

### **Unit Test**

```php
<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\CleanupUsersCommand;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class CleanupUsersCommandTest extends TestCase
{
    public function testDryRunShowsUsers(): void
    {
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->method('findUnverifiedBefore')->willReturn([]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('remove');

        $command = new CleanupUsersCommand($userRepository, $em, new NullLogger());

        $application = new Application();
        $application->add($command);

        $tester = new CommandTester($application->find('app:cleanup-users'));
        $tester->execute(['--dry-run' => true, '--days' => '7']);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('Dry run', $tester->getDisplay());
    }
}
```

### **Integration Test**

```php
<?php

declare(strict_types=1);

namespace App\Tests\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ImportProductsCommandTest extends KernelTestCase
{
    public function testImportFromCsv(): void
    {
        self::bootKernel();

        $application = new Application(self::$kernel);
        $command = $application->find('app:import-products');

        $tester = new CommandTester($command);
        $tester->execute([
            'file' => __DIR__ . '/fixtures/products.csv',
            '--skip-header' => true,
        ]);

        $tester->assertCommandIsSuccessful();
        $this->assertStringContainsString('Import complete', $tester->getDisplay());
    }

    public function testImportFailsWithMissingFile(): void
    {
        self::bootKernel();

        $application = new Application(self::$kernel);
        $command = $application->find('app:import-products');

        $tester = new CommandTester($command);
        $tester->execute(['file' => '/nonexistent/file.csv']);

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString('File not found', $tester->getDisplay());
    }
}
```

## DDEV Commands

```bash
# Run a command
ddev exec bin/console app:my-command

# Run with arguments and options
ddev exec bin/console app:import-products /path/to/file.csv --skip-header --dry-run

# List all registered commands
ddev exec bin/console list

# List commands in the app namespace
ddev exec bin/console list app

# Get help for a command
ddev exec bin/console app:my-command --help

# Run in verbose mode
ddev exec bin/console app:my-command -v
ddev exec bin/console app:my-command -vv
ddev exec bin/console app:my-command -vvv

# Generate a command with MakerBundle
ddev exec bin/console make:command app:my-new-command

# Run command tests
ddev exec bin/phpunit tests/Command/

# Clear cache
ddev exec bin/console cache:clear
```

## Best Practices

**Command Design**
- Use `#[AsCommand]` attribute for registration (no `configure()` name needed)
- Keep commands thin: delegate business logic to services
- Always return `Command::SUCCESS`, `Command::FAILURE`, or `Command::INVALID`
- Support `--dry-run` for destructive operations
- Use `SymfonyStyle` for consistent, readable output

**Arguments and Options**
- Use arguments for required, positional input
- Use options for flags and optional configuration
- Provide sensible defaults for options
- Validate input early and provide clear error messages

**Output**
- Use `SymfonyStyle` methods instead of raw `$output->writeln()`
- Show progress bars for long-running operations
- Display summaries with `definitionList` or `table`
- Use `success`, `error`, `warning` for final status messages
- Support `--format=json` for machine-readable output

**Error Handling**
- Catch exceptions and provide user-friendly error messages
- Log errors with context for debugging
- Return appropriate exit codes
- Show partial results when possible (import 95 of 100 rows)

**Performance**
- Flush database writes in batches for imports
- Call `$em->clear()` to free memory during large operations
- Use progress bars so users know the command is working
- Set memory limits appropriately for large data sets

**Testing**
- Use `CommandTester` for isolated command testing
- Test all exit paths (success, failure, invalid input)
- Test interactive prompts with `setInputs()`
- Mock services for unit tests, use real services for integration tests

Generate production-ready Symfony Console commands that work seamlessly in DDEV environments.

## Next Steps

After creating a command, consider:
- `/test-new` — Write unit and integration tests with CommandTester
- `/messenger-new` — Dispatch async messages from the command
