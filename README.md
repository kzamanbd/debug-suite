# Debug Suite – Upcoming Features

The Debug Suite plugin is designed to provide powerful debugging and inspection tools for WordPress developers. Here are some of the upcoming features planned for future releases:

## Upcoming Features

1. **File Manager**
   - Manage all files within your WordPress installation directly from the admin panel.

2. **Query Console**
   - Run custom database queries securely from a built-in console interface.

3. **WordPress Hook Inspector**
   - Inspect, search, and analyze all registered WordPress hooks (actions and filters) in real time.

4. **Ajax Inspector**
   - Monitor and debug all AJAX requests and responses within your WordPress site.

Stay tuned for more updates and enhancements!

---

## Contribution Guide

We welcome contributions from the community! To contribute to Debug Suite, please follow these guidelines:

1. **Fork the Repository**
   - Create your own fork and work on a feature branch.

2. **Coding Standards**
   - Follow PSR-12 and WordPress coding standards for PHP.
   - Use TypeScript and follow ESLint rules for frontend code.
   - Use Tailwind CSS v4 for styling React components.

3. **Commit Messages**
   - Write clear, descriptive commit messages.

4. **Testing**
   - Add unit tests for new features and bug fixes.
   - Ensure all tests pass before submitting a pull request.

5. **Pull Requests**
   - Submit a pull request with a clear description of your changes.
   - Reference related issues if applicable.

6. **Documentation**
   - Update documentation and README as needed for your changes.

7. **Code Review**
   - Participate in code reviews and address feedback promptly.

Thank you for helping make Debug Suite better!

---

## Development Guide

This section provides essential information for developing and extending the Debug Suite plugin.

### Prerequisites

- PHP 8.1 or higher
- Node.js (v18+ recommended)
- pnpm (preferred) or npm/yarn
- Composer
- WordPress (latest stable version)

### Project Structure

- **PHP Backend:** Located in the `includes/` directory, following PSR-4 autoloading and namespacing under `DebugSuite`.
- **Frontend:** React/TypeScript code in the `src/` directory, styled with Tailwind CSS v4.
- **Assets:** Compiled assets in the `assets/` directory.

### Setup Instructions

1. **Clone the Repository**
2. **Install PHP Dependencies:**

   ```sh
   composer install
   ```

3. **Install JS Dependencies:**

   ```sh
   pnpm install
   # or
   npm install
   ```

4. **Build Frontend Assets:**

   ```sh
   pnpm run build
   # or
   npm run build
   ```

5. **Enable the Plugin:**
   - Copy/symlink the plugin folder to your WordPress `wp-content/plugins` directory.
   - Activate via the WordPress admin panel.

### Coding Standards

- **PHP:**
  - PSR-12 and WordPress coding standards enforced via `phpcs.xml`.
  - Use full DocBlocks for all classes, methods, and properties.
- **TypeScript/JS:**
  - ESLint rules enforced.
  - Use interfaces and proper typing.
- **CSS:**
  - Use Tailwind CSS v4 utility classes and the Oxide engine.

### Testing

- **PHP:**
  - Use PHPUnit for backend tests.
- **JS/TS:**
  - Add tests as needed for frontend logic.

### Useful Commands

- Lint PHP: `vendor/bin/phpcs`
- Fix PHP: `vendor/bin/phpcbf`
- Lint JS/TS: `pnpm run lint` or `npm run lint`
- Build assets: `pnpm run build` or `npm run build`
- Run tests: `vendor/bin/phpunit`

### Additional Notes

- Follow the service provider and dependency injection patterns.
- Register new services/providers in the appropriate manager class.
- Document all new features and update the README.

For more details, see the `/docs` directory and inline code comments.

## Requirements

- WordPress 5.7 or higher
- PHP 8.1 or higher
