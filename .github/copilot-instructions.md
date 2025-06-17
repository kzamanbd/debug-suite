# GitHub Copilot Instructions for Debug Suite Plugin

This document provides guidelines for GitHub Copilot to assist with the development of the Debug Suite WordPress plugin, a development toolkit designed to make WordPress debugging and inspection more efficient.

## Project Overview

Debug Suite is a WordPress plugin that provides debugging tools for WordPress developers. It follows a service provider pattern with a **PSR-11 compliant dependency injection container** that includes **PHP-DI compatibility features**, uses PSR-4 autoloading, and has a React/TypeScript frontend with Tailwind CSS v4 for styling.

### Core Architecture

- **PSR-11 Compliant DI Container**: Full compliance with PSR-11 DI Interface specification
- **PHP-DI Style Definitions**: Support for autowiring, factory, and value definitions
- **Enhanced Service Provider System**: Lifecycle management with registration and booting phases
- **Container Builder Pattern**: Fluent interface for container configuration
- **WordPress Integration**: Seamless integration with WordPress hooks and lifecycle

## Code Standards and Requirements

### PHP Backend

1. **PHP Version**: Use PHP 8.2 features including:

    - Union types
    - Named arguments
    - Constructor property promotion
    - Match expressions
    - Readonly properties
    - First-class callable syntax
    - DNF (Disjunctive Normal Form) types

2. **Type Hinting**:

    - Use return type declarations for all methods and functions
    - Use parameter type hints for all method and function parameters
    - Use union types where appropriate (`string|null`, etc.)
    - Use nullable types when applicable

3. **Coding Standards**:

    - Follow PSR-12 coding standard
    - Follow WordPress coding standards (where not in conflict with PSR-12)
    - Use full DocBlocks for all classes, methods, and properties
    - Use PHP_CodeSniffer rules defined in `phpcs.xml`

4. **Autoloading**:
    - Follow PSR-4 autoloading standards
    - Namespace all classes appropriately under the `DebugSuite` namespace

### JavaScript/TypeScript Frontend

1. **TypeScript**:

    - Use TypeScript for all frontend components
    - Properly type all variables, functions, and components
    - Use interfaces for object shapes

2. **React**:

    - Use `@wordpress/element` for React components
    - Follow functional component patterns with hooks
    - Use typed props for all components

3. **ESLint**:

    - Follow ESLint rules for JavaScript and TypeScript
    - Maintain code quality with ESLint static analysis

4. **Tailwind CSS v4**:

    - Use Tailwind CSS v4 with the Oxide engine for styling
    - **Always use the `primary` color as the brand color for all UI elements.**
    - Follow utility-first CSS approach with Tailwind classes
    - Use `@tailwindcss/postcss` for PostCSS integration
    - **Use the `classNames` utility from `@/utils` for conditional class merging instead of using `tailwind-merge` directly.**
    - Maintain consistent spacing and sizing using Tailwind's design system
    - Utilize Tailwind v4's CSS variables system for theme customization
    - Apply responsive design using Tailwind's breakpoint utilities

5. **React Component String internationalization**:

    - Use `@wordpress/i18n` for string internationalization
    - Use `__()` and `_x()` functions for translating strings
    - Ensure all user-facing strings are translatable

6. **Icons**:

    - **Always use Lucide React icons for all UI icons**
    - Import icons from `lucide-react` package
    - Never use inline SVG code or other icon libraries
    - Use consistent icon sizing (typically 16px or 24px)
    - Example: `import { FolderOpen, Settings, X } from 'lucide-react';`

## Architecture Guidelines

1. **PSR-11 Dependency Injection Container**:

    - Follow PSR-11 DI Interface specification
    - Use `DebugSuite\Core\Container\Container` which implements `Psr\Container\ContainerInterface`
    - Support for `get()` and `has()` methods with proper exception handling
    - Throw `DebugSuite\Core\Container\Exceptions\NotFoundException` for missing services
    - Throw `DebugSuite\Core\Container\Exceptions\ContainerException` for container errors

2. **PHP-DI Style Definitions**:

    - Use `AutowiredDefinition` for automatic dependency resolution
    - Use `FactoryDefinition` for factory-based service creation
    - Use `ValueDefinition` for static values and configuration
    - Support for singleton and transient service lifetimes

3. **Service Providers**:

    - Extend `DebugSuite\Core\Container\AbstractServiceProvider` for new service providers
    - Register services with the container in the `register()` method
    - Boot services and register hooks in the `boot()` method
    - Use proper dependency injection for all service dependencies
    - List provided services in the `$provides` array property

4. **Container Builder**:

    - Use `DebugSuite\Core\Container\ContainerBuilder` for container configuration
    - Enable autowiring for automatic dependency resolution
    - Add definitions using the fluent interface
    - Configure container settings before building

5. **Service Manager**:

    - Use `DebugSuite\Core\Container\ServiceManager` for provider lifecycle management
    - Register all providers before booting any
    - Automatically register hooks for `Hookable` services
    - Resolve services through the container

6. **Hookable Interface**:

    - Implement `Hookable` interface for classes that need to register WordPress hooks
    - Use `register_hooks()` method to register hooks
    - Hook registration is handled automatically by the ServiceManager

7. **Debug Providers**:
    - Extend `AbstractDebugProvider` for new debug providers
    - Implement the `DebugProviderInterface` methods

## Security and Performance

1. **Security**:

    - Validate and sanitize all input data
    - Use WordPress security functions (`esc_html`, `esc_attr`, etc.)
    - Follow WordPress security best practices
    - Implement nonce verification for forms
    - Use capability checks for all admin actions

2. **Performance**:
    - Optimize database queries
    - Cache results where appropriate
    - Minimize the use of global variables
    - Use WordPress transients for caching when appropriate

## Code Structure

- **Maintain separation of concerns**:
    - Core services in the `Core` namespace
    - DI Container system in the `Core\Container` namespace
    - Admin interfaces in the `Admin` namespace
    - Frontend code in the `Frontend` namespace
    - Debug providers in the `Providers` namespace
    - Manager classes in the `Managers` namespace
    - Interfaces in the `Interfaces` namespace

## Error Handling

- Use proper exception handling with typed exceptions
- Log errors appropriately
- Provide meaningful error messages
- Use typed exceptions where appropriate

## Documentation

- All public methods and classes should have complete DocBlocks
- Include `@since` tags in DocBlocks to track when features were added
- Include `@return`, `@param`, and `@throws` tags in DocBlocks where applicable
- Document complex logic with inline comments
- Update README.md with new features

## DocBlock Format

All DocBlocks should follow this format:

```php
/**
 * Short description of the class/method/property.
 *
 * Longer description if needed with more details about functionality,
 * implementation details, or usage examples.
 *
 * @since DEBUG_SUITE_SINCE
 *
 * @param string $param1 Description of the first parameter.
 * @param int    $param2 Description of the second parameter.
 *
 * @return string|null Description of the return value.
 *
 * @throws \Exception When something goes wrong.
 */
```

## Testing

- Write unit tests for critical functionality
- Ensure backward compatibility
- Test with various WordPress versions

## WordPress Integration

- Follow WordPress plugin API best practices
- Use WordPress hooks system properly
- Integrate with WordPress admin UI consistently
- Support WordPress internationalization
- Use WordPress capabilities system for access control

## File Structure Templates

### PHP Class Template

```php
<?php
/**
 * Short description of this file.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Example;

use SomeNamespace\SomeClass;

/**
 * Class description.
 *
 * More detailed description if needed.
 *
 * @since DEBUG_SUITE_SINCE
 */
class ExampleClass {
    // Class implementation
}
```

### Interface Template

```php
<?php
/**
 * Short description of this file.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Interfaces;

/**
 * Interface description.
 *
 * More detailed description if needed.
 *
 * @since DEBUG_SUITE_SINCE
 */
interface ExampleInterface {
    // Interface methods
}
```

### React Component File Naming Convention

**All React component files in `src/components` and its subdirectories must:**

- Use lowercase letters only
- Use hyphens (`-`) as word separators
- Have the `.tsx` extension
- Example: `my-component.tsx`, `logs-skeleton.tsx`, `settings-skeleton.tsx`, `base-layout.tsx`

### React Component and Directory Structure Guidelines

#### 1. Reusable Components

- Store all **reusable UI components** in `src/components` or its subdirectories.
- File names must be **lowercase** and use **hyphens** as word separators (e.g., `user-profile-card.tsx`).
- Directory names under `src/components` must also be **lowercase** and **hyphen-separated** (e.g., `ui/`, `form-fields/`).

#### 2. Feature-Labeled Components

- Store **feature-specific components** inside their respective feature folders (e.g., `src/pages/feature-x/feature-x-table.tsx`).
- Feature directories must be **lowercase** and **hyphen-separated**.
- Do **not** place feature-specific components in `src/components`.

#### 3. General Rules

- Do **not** use camelCase, PascalCase, or underscores in file or directory names for components.
- Do **not** use `index.tsx` for component exports.
- Subcomponents should follow the same naming convention as above.

---

**Examples:**

- Good: `src/components/ui/button.tsx`, `src/pages/file-logs/file-logs-table.tsx`
- Bad: `src/components/UserProfileCard.tsx`, `src/pages/FileLogs/FileLogsTable.tsx`, `src/components/user_profile_card.tsx`, `src/components/User-Profile-Card.tsx`

---

**Enforce these conventions for all new and refactored React component files and directories.**

### Rationale

- This matches the current project structure (see: `src/components/logs-skeleton.tsx`, `src/components/settings-skeleton.tsx`, `src/components/base-layout.tsx`)
- Ensures consistency and clarity in file naming
- Avoids camelCase, PascalCase, or underscores in file names

## Additional Guidelines

- Each component should be in its own file unless it is a small utility or subcomponent.
- Index files (e.g., `index.tsx`) are not used for component exports in this project.
- Subcomponents should follow the same naming convention.

---

**Enforce this convention for all new and refactored React component files.**

### React Component Template with Tailwind CSS v4

```tsx
/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { classNames } from '@/utils';
import { FolderOpen, Settings, X } from 'lucide-react';

/**
 * Internal dependencies
 */
import { SomeType } from '../types';

/**
 * Props for the ExampleComponent component.
 *
 * @since DEBUG_SUITE_SINCE
 */
interface ExampleComponentProps {
    title: string;
    items: Array<SomeType>;
    onAction: (id: number) => void;
    className?: string;
}

/**
 * ExampleComponent component.
 *
 * Description of what this component does.
 *
 * @since DEBUG_SUITE_SINCE
 */
const ExampleComponent = ({ title, items, onAction, className = '' }: ExampleComponentProps): JSX.Element => {
    const [activeItem, setActiveItem] = useState<number | null>(null);

    // Component implementation

    return (
        <div className={classNames('rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800', className)}>
            <h2 className="text-lg font-medium text-gray-900 dark:text-white">{title}</h2>

            <ul className="mt-3 space-y-2">
                {items.map((item) => (
                    <li
                        key={item.id}
                        className={classNames(
                            'cursor-pointer rounded p-2 transition-colors',
                            activeItem === item.id
                                ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300'
                                : 'hover:bg-gray-50 dark:hover:bg-gray-700/50'
                        )}
                        onClick={() => {
                            setActiveItem(item.id);
                            onAction(item.id);
                        }}
                    >
                        <FolderOpen size={16} className="mr-2 inline" />
                        {item.name}
                    </li>
                ))}
            </ul>
        </div>
    );
};

export default ExampleComponent;
```
