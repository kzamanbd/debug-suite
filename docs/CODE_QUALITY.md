# Debug Suite - Code Quality Tools

This document explains how to use the code quality tools configured for the Debug Suite WordPress plugin.

## Tools Available

### 1. PHP CodeSniffer (PHPCS)

PHPCS checks your code against the WordPress coding standards.

### 2. PHP Code Beautifier and Fixer (PHPCBF)

PHPCBF automatically fixes code style issues that can be corrected automatically.

### 3. PHPStan

PHPStan performs static analysis to find bugs in your code.

## Composer Scripts

The following scripts are available via `composer run <script-name>`:

### PHPCS Commands

- `composer phpcs` - Run PHPCS with the configured standard
- `composer phpcs:check` - Run PHPCS with summary report  
- `composer phpcs:full` - Run PHPCS with full detailed report
- `composer phpcs:fix` - Run PHPCBF to fix auto-fixable issues
- `composer phpcs:dry-run` - Preview what PHPCBF would fix without making changes
- `composer phpcbf` - Alias for phpcs:fix

### Quality Assurance

- `composer phpstan` - Run PHPStan static analysis
- `composer qa` - Run both PHPCS check and PHPStan
- `composer fix` - Alias for phpcs:fix
- `composer fix-all` - Fix issues and then check again

### Testing

- `composer test` - Run PHPUnit tests

## Quick Start

1. **Check for coding standard violations:**

   ```bash
   composer phpcs:check
   ```

2. **Fix auto-fixable issues:**

   ```bash
   composer phpcs:fix
   ```

3. **Run full quality check:**

   ```bash
   composer qa
   ```

4. **Fix all issues and verify:**

   ```bash
   composer fix-all
   ```

## Using the Shell Script

You can also use the provided shell script for a comprehensive check:

```bash
./run-phpcs.sh
```

This script will:

1. Show installed PHPCS standards
2. Run PHPCS summary report
3. Run PHPCS full report  
4. Auto-fix issues with PHPCBF
5. Show remaining issues

## Configuration Files

- `phpcs.xml` - PHPCS configuration with WordPress coding standards
- `composer.json` - Contains all the script definitions

## WordPress Coding Standards

The project is configured to follow:

- WordPress Core coding standards
- PHP Compatibility for PHP 7.4+
- Custom rules for array syntax

## Continuous Integration

These tools can be integrated into your CI/CD pipeline by running:

```bash
composer qa
```

This will fail if there are any coding standard violations or static analysis issues.

## IDE Integration

Most modern IDEs can integrate with PHPCS to show violations in real-time:

- VS Code: Install "PHP Sniffer" extension
- PhpStorm: Built-in PHPCS support
- Sublime Text: Install "PHP_CodeSniffer" package

Configure your IDE to use:

- PHPCS path: `vendor/bin/phpcs`
- Standard: `phpcs.xml`
- PHPCBF path: `vendor/bin/phpcbf`
