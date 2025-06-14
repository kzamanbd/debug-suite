# DocBlock Standards for Debug Suite

This document outlines the DocBlock standards used in the Debug Suite plugin.

## Basic DocBlock Structure

All DocBlocks should follow this format:

```php
/**
 * Short description of the class/method/property.
 *
 * Longer description if needed with more details about functionality,
 * implementation details, or usage examples.
 *
 * @since 1.0.0
 *
 * @param string $param1 Description of the first parameter.
 * @param int    $param2 Description of the second parameter.
 *
 * @return string|null Description of the return value.
 *
 * @throws \Exception When something goes wrong.
 */
```

## File Header DocBlock

Every PHP file should start with a file header DocBlock:

```php
<?php
/**
 * Short description of this file.
 *
 * @package DebugSuite
 */
```

## Class DocBlock

Classes should have a DocBlock with at least a short description and `@since` tag:

```php
/**
 * Class description.
 *
 * More detailed description if needed.
 *
 * @since 1.0.0
 */
class ExampleClass {
    // ...
}
```

## Property DocBlock

Properties should have a DocBlock with at least a short description, `@since` tag, and `@var` tag:

```php
/**
 * Property description.
 *
 * @since 1.0.0
 * @var string
 */
private string $property;
```

## Method DocBlock

Methods should have a DocBlock with at least a short description, `@since` tag, `@param` tags for each parameter, and a `@return` tag:

```php
/**
 * Method description.
 *
 * @since 1.0.0
 * @param string $param1 Description of the first parameter.
 * @return string|null Description of the return value.
 */
public function exampleMethod(string $param1): ?string {
    // ...
}
```

## Interface DocBlock

Interfaces should have a DocBlock with at least a short description and `@since` tag:

```php
/**
 * Interface description.
 *
 * @since 1.0.0
 */
interface ExampleInterface {
    // ...
}
```

## Common Tags

- `@since` - The version when the element was introduced
- `@param` - For method parameters (include type, variable name, and description)
- `@return` - For method return values (include type and description)
- `@throws` - For exceptions that might be thrown (include exception class and when it's thrown)
- `@var` - For properties (include type)
- `@deprecated` - For deprecated elements (include version when deprecated and what to use instead)
- `@see` - For references to other elements or external resources

## Type Formatting

- Use full type names: `string`, `int`, `bool`, `array`, `float`, `object`, etc.
- For arrays of specific types, use `array<string, mixed>` or similar
- For nullable types, use `string|null` format in DocBlocks
- For union types, use pipe separator: `string|int|bool`

## Alignment

For multiple `@param` tags, align the variable names and descriptions:

```php
/**
 * @param string $param1   Description of the first parameter.
 * @param int    $param2   Description of the second parameter.
 */
```

## Line Length

Try to keep DocBlock lines under 100 characters for readability.
