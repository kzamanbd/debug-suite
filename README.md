# Debug Suite - WordPress Developer Toolkit

[![WordPress Plugin Version](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)](https://wordpress.org/)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-purple.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2%2B-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![TypeScript](https://img.shields.io/badge/TypeScript-5.0%2B-blue.svg)](https://www.typescriptlang.org/)

Debug Suite is a comprehensive, all-in-one WordPress development toolkit designed to make debugging, monitoring, and site management faster, safer, and more intuitive. Whether you're building, maintaining, or debugging WordPress sites, this suite provides all the essential tools you need in one unified interface.

## ✨ Key Features

### 🔧 Debug Configuration Management

- **Intuitive Settings Interface**: Manage WordPress debug constants (`WP_DEBUG`, `WP_DEBUG_LOG`, `WP_DEBUG_DISPLAY`) through a user-friendly dashboard
- **Safe wp-config.php Management**: Automated reading and writing of debug settings with validation and backup
- **One-Click Reset**: Instantly restore debug settings to safe defaults
- **Configuration Validation**: Comprehensive validation ensures your debug settings are always correct

### 📋 Advanced Log Management

- **Intelligent Log Parsing**: Parse and display WordPress debug logs with beautiful syntax highlighting
- **Smart Filtering**: Filter log entries by severity level (Error, Warning, Notice, Fatal, etc.)
- **Full-Text Search**: Search through log entries with real-time highlighting
- **Comprehensive Statistics**: View detailed analytics about log file size, entry counts, and trends
- **Auto-Discovery**: Automatically detect debug log files across different WordPress configurations
- **Safe Log Operations**: Clear logs with automatic backup creation

### Dashboard Overview

- **System Health**: Real-time display of PHP version, WordPress version, and debug status
- **Quick Statistics**: Instant overview of log activity and system health indicators
- **Fast Actions**: Quick access to the most common debugging tasks
- **Visual Indicators**: Clear visual feedback for system status and potential issues

### 🚀 Onboarding Experience

- **Guided Setup**: Step-by-step wizard for first-time users
- **Best Practices**: Built-in recommendations for optimal debug configuration
- **Interactive Tutorial**: Learn the interface with hands-on guidance

## 🏗️ Modern Architecture

### Enterprise-Grade PHP Backend

- **Modern PHP 8.1+**: Built with cutting-edge PHP features including typed properties, union types, and constructor property promotion
- **Clean Architecture**: PSR-4 autoloading, PSR-12 coding standards, and dependency injection container
- **Service Layer Pattern**: Clean separation of concerns with dedicated business logic services
- **Comprehensive Testing**: Full unit and integration test coverage with PHPUnit

### React TypeScript Frontend

- **Type-Safe Frontend**: Full TypeScript implementation for robust frontend development
- **Modern React**: Built with React hooks and functional components
- **Responsive Design**: Mobile-first design using Tailwind CSS v4 with the Oxide engine
- **Accessibility First**: WCAG-compliant interfaces using Headless UI components

### RESTful API

- **Comprehensive Endpoints**: Full REST API covering all plugin functionality
- **Security First**: Proper permission checks and request validation
- **Error Handling**: Consistent error responses with appropriate HTTP status codes
- **Type Safety**: Complete type hints and return type declarations

## 🛠️ Technical Specifications

### System Requirements

- **PHP**: 8.1 or higher
- **WordPress**: 6.0+ (tested up to 6.8)
- **Memory**: 128MB+ recommended
- **Disk Space**: 10MB for plugin files

### Technology Stack

- **Backend**: PHP 8.1+, WordPress REST API, PSR-4 Autoloading
- **Frontend**: React, TypeScript, Tailwind CSS v4, WordPress Scripts
- **Build Tools**: pnpm, ESLint, PHPCodeSniffer, PHPStan
- **Testing**: PHPUnit, WordPress Test Suite

### Performance Features

- **Lazy Loading**: Services loaded only when needed
- **Optimized Assets**: Compressed and optimized JavaScript and CSS
- **Efficient Caching**: Smart caching strategies for file operations
- **Minimal Footprint**: Lightweight with minimal resource usage

## 📖 Getting Started

### Installation

1. **WordPress Admin Installation**:
   - Navigate to `Plugins > Add New` in your WordPress admin
   - Search for "Debug Suite"
   - Click "Install Now" and then "Activate"

2. **Manual Installation**:
   - Download the plugin ZIP file
   - Upload to `/wp-content/plugins/debug-suite/`
   - Activate through the WordPress plugins screen

3. **Composer Installation** (for developers):

   ```bash
   composer require kzamanbd/debug-suite
   ```

### First Steps

1. **Access Debug Suite**: Look for "Debug Suite" in your WordPress admin menu
2. **Complete Onboarding**: Follow the guided setup wizard
3. **Configure Debug Settings**: Set up your preferred debug configuration
4. **Start Debugging**: Use the various tools to monitor and debug your site

### Quick Actions

- **View Debug Logs**: Navigate to `Debug Suite > Manage Logs`
- **Configure Settings**: Visit `Debug Suite > Debug Config`
- **Dashboard Overview**: Check `Debug Suite > Overview`

## 🔧 Development Setup

### Prerequisites

- PHP 8.1 or higher
- Node.js (v18+ recommended)
- Composer
- pnpm (preferred) or npm

### Local Development

1. **Clone and Install**:

   ```bash
   git clone https://github.com/kzamanbd/debug-suite.git
   cd debug-suite
   composer install
   pnpm install
   ```

2. **Development Build**:

   ```bash
   pnpm run dev  # Watches for changes
   ```

3. **Production Build**:

   ```bash
   pnpm run build
   ```

### Development Commands

```bash
# Frontend development
pnpm run dev           # Development with watch mode
pnpm run build         # Production build
pnpm run lint          # ESLint checking
pnpm run type-check    # TypeScript validation

# PHP development
composer install       # Install dependencies
vendor/bin/phpcs      # Code style checking
vendor/bin/phpcbf     # Code style fixing
vendor/bin/phpunit    # Run tests
vendor/bin/phpstan    # Static analysis
```

## 🧪 Testing

Debug Suite includes comprehensive testing coverage:

### PHP Testing

```bash
vendor/bin/phpunit                    # Run all tests
vendor/bin/phpunit --group unit       # Unit tests only
vendor/bin/phpunit --group integration # Integration tests only
vendor/bin/phpunit --coverage-html coverage # Generate coverage report
```

### Code Quality

```bash
vendor/bin/phpcs                      # Check coding standards
vendor/bin/phpstan analyse           # Static analysis
```

## 🤝 Contributing

We welcome contributions from the community! Here's how you can help:

### Contribution Guidelines

1. **Fork the Repository**: Create your own fork and work on a feature branch
2. **Coding Standards**:
   - Follow PSR-12 and WordPress coding standards for PHP
   - Use TypeScript and ESLint rules for frontend code
   - Use Tailwind CSS v4 for styling
3. **Testing**: Add tests for new features and ensure all tests pass
4. **Documentation**: Update documentation for any new features
5. **Pull Requests**: Submit clear pull requests with detailed descriptions

### Code Standards

- **PHP**: PSR-12 coding standards with WordPress conventions
- **TypeScript**: Strict TypeScript with comprehensive type coverage
- **Testing**: Unit and integration tests for all new functionality
- **Documentation**: PHPDoc for all classes and methods

## 📚 Documentation

- **[Architecture Guide](documentation/README.md)**: Deep dive into the plugin architecture
- **[REST API Reference](documentation/REST_API_ARCHITECTURE.md)**: Complete API documentation
- **[Testing Guide](documentation/TESTING.md)**: Comprehensive testing documentation
- **[Hookable Interface](documentation/HOOKABLE_INTERFACE.md)**: WordPress hooks integration

## 🔐 Security

Debug Suite takes security seriously:

- **Capability Checks**: All operations require appropriate WordPress capabilities
- **Input Validation**: Comprehensive sanitization of all user input
- **Path Protection**: Prevents directory traversal and unauthorized file access
- **Backup System**: Automatic backups before any destructive operations
- **Secure API**: All REST endpoints properly secured and validated

## 🌍 Translation

Debug Suite is fully internationalized and ready for translation:

- **Text Domain**: `debug-suite`
- **Translation Files**: Located in `/languages` directory
- **POT File**: Available for translators
- **WordPress.org**: Translations managed through WordPress.org

## 📝 Changelog

See [CHANGELOG.md](CHANGELOG.md) for detailed release notes and version history.

## 📄 License

Debug Suite is licensed under the [GPL v2 or later](https://www.gnu.org/licenses/gpl-2.0.html).

## 🆘 Support

- **Documentation**: Check the `/documentation` directory for detailed guides
- **Issues**: Report bugs on [GitHub Issues](https://github.com/kzamanbd/debug-suite/issues)
- **Community**: Join discussions on [GitHub Discussions](https://github.com/kzamanbd/debug-suite/discussions)

## 🚀 Roadmap

### Upcoming Features (v1.1+)

- **Query Console**: Execute and analyze database queries safely
- **WordPress Hook Inspector**: Real-time hook monitoring and analysis
- **Ajax Inspector**: Monitor and debug AJAX requests
- **Performance Profiler**: Identify performance bottlenecks
- **Plugin Conflict Detector**: Identify conflicting plugins
- **Database Browser**: Visual database exploration tools

### Long-term Vision

- **Multi-site Management**: Enhanced multisite debugging tools
- **Remote Monitoring**: Monitor multiple sites from a central dashboard
- **Advanced Logging**: Custom log channels and advanced filtering
- **Integration APIs**: Third-party tool integrations

## 🙏 Acknowledgments

Special thanks to the WordPress community and all contributors who have helped make Debug Suite a powerful and reliable debugging toolkit for WordPress developers worldwide.

---

### **Made with ❤️ for the WordPress Community**

Debug Suite aims to make WordPress development more enjoyable and productive. Whether you're a seasoned developer or just starting with WordPress, we hope this toolkit helps you build amazing websites with confidence.

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
