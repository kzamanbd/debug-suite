# Debug Suite Documentation

This directory contains focused documentation for the Debug Suite WordPress plugin. The main development guidelines and architecture patterns are documented in the **[GitHub Copilot Instructions](../.github/copilot-instructions.md)**.

## 📖 Documentation Index

### Development Guidelines

- **[GitHub Copilot Instructions](../.github/copilot-instructions.md)** - **📚 PRIMARY REFERENCE** - Complete development guidelines, architecture patterns, and implementation examples
- **[TESTING.md](TESTING.md)** - Testing strategies and patterns specific to Debug Suite
- **[DOCBLOCK.md](DOCBLOCK.md)** - Documentation standards and PHPDoc guidelines

### WordPress Integration Patterns

- **[REST_API_ARCHITECTURE.md](REST_API_ARCHITECTURE.md)** - REST API architecture with dependency injection
- **[HOOKABLE_INTERFACE.md](HOOKABLE_INTERFACE.md)** - WordPress hook integration interface

## 🚀 Quick Start

### For New Developers

1. **Start with [GitHub Copilot Instructions](../.github/copilot-instructions.md)** - This is your main reference document
2. Review **[TESTING.md](TESTING.md)** for testing patterns specific to this plugin
3. Check **[REST_API_ARCHITECTURE.md](REST_API_ARCHITECTURE.md)** for API development patterns

### For WordPress Integration

1. **[HOOKABLE_INTERFACE.md](HOOKABLE_INTERFACE.md)** - WordPress hook integration patterns
2. **[REST_API_ARCHITECTURE.md](REST_API_ARCHITECTURE.md)** - API controller patterns

## 🏗️ Documentation Philosophy

**Consolidated Approach**: We've streamlined the documentation to avoid redundancy. The **[GitHub Copilot Instructions](../.github/copilot-instructions.md)** serves as the primary reference containing:

- Complete architecture guidelines
- Container system implementation patterns  
- Service layer architecture
- Feature implementation workflows
- Advanced dependency injection examples
- Environment-aware service configuration
- Helper function usage patterns

The remaining documentation files focus on specialized WordPress integration patterns and testing strategies specific to this plugin.

## 📚 Key Features Documented

### Container System (in Copilot Instructions)

- PSR-11 compliance with proper exception handling
- Advanced autowiring with multiple parameter injection strategies
- Environment-aware service configuration
- Service decoration patterns
- Tagged services for event systems
- Container builder with fluent interface

### WordPress Integration (in this directory)

- Automatic hook registration via Hookable interface
- REST API controllers with dependency injection
- Service provider lifecycle management
- WordPress-specific testing patterns

### Development Patterns (in Copilot Instructions)

- Service layer architecture for business logic separation
- REST API controllers with dependency injection
- ServiceResponse pattern for consistent error handling
- Testing patterns with mock dependencies
- Environment-specific configuration management

## 📝 Contributing

When updating documentation:

1. **Architecture & Implementation Patterns** → Update the [GitHub Copilot Instructions](../.github/copilot-instructions.md)
2. **WordPress-Specific Integration** → Update files in this directory
3. **Testing Strategies** → Update [TESTING.md](TESTING.md)

This approach ensures a single source of truth for core development patterns while maintaining focused documentation for specialized WordPress integration concerns.
