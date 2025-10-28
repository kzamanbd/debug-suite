# Debug Suite - WordPress Developer Toolkit

[![WordPress Plugin Version](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)](https://wordpress.org/)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-purple.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2%2B-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![TypeScript](https://img.shields.io/badge/TypeScript-5.0%2B-blue.svg)](https://www.typescriptlang.org/)

A comprehensive WordPress development toolkit designed to make debugging, monitoring, and site management faster, safer, and more intuitive.

## ✨ Features

- **Debug Configuration Management**: Manage WordPress debug constants through an intuitive dashboard
- **Advanced Log Management**: Parse and display debug logs with syntax highlighting, filtering, and search
- **System Health Dashboard**: Real-time display of PHP/WordPress versions and debug status
- **Guided Onboarding**: Step-by-step wizard for first-time users
- **Modern React/TypeScript Interface**: Built with Tailwind CSS v4 and accessibility-first design

## 🛠️ Requirements

- **PHP**: 8.1+
- **WordPress**: 6.0+ (tested up to 6.8)
- **Memory**: 128MB+ recommended

## 📖 Installation

### WordPress Admin

1. Navigate to `Plugins > Add New`
2. Search for "Debug Suite"
3. Install and activate

### Manual Installation

1. Download plugin ZIP
2. Upload to `/wp-content/plugins/debug-suite/`
3. Activate through WordPress admin

## 🔧 Development Setup

```bash
# Clone and install dependencies
git clone https://github.com/kzamanbd/debug-suite.git
cd debug-suite
composer install
pnpm install

# Development build
pnpm run dev

# Production build
pnpm run build
```

## 🧪 Testing

```bash
# Run all tests
vendor/bin/phpunit

# Code quality checks
vendor/bin/phpcs
vendor/bin/phpstan analyse
```

## 🏗️ Architecture

- **Backend**: Modern PHP 8.1+ with PSR-4 autoloading, dependency injection, and service layer pattern
- **Frontend**: React with TypeScript and Tailwind CSS v4
- **API**: RESTful endpoints with proper security and validation
- **Testing**: Full PHPUnit coverage for unit and integration tests

## 📚 Documentation

- [Architecture Guide](docs/README.md)
- [REST API Reference](docs/REST_API_ARCHITECTURE.md)
- [Testing Guide](docs/TESTING.md)
- [Hookable Interface](docs/HOOKABLE_INTERFACE.md)

## 🤝 Contributing

1. Fork the repository
2. Follow PSR-12 and WordPress coding standards
3. Add tests for new features
4. Submit clear pull requests

## 📄 License

GPL v2 or later - see [LICENSE.txt](LICENSE.txt)

## 🆘 Support

- [GitHub Issues](https://github.com/kzamanbd/debug-suite/issues)
- [GitHub Discussions](https://github.com/kzamanbd/debug-suite/discussions)
- [Documentation](docs/)

---

Made with ❤️ for the WordPress Community
