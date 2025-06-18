# Debug Suite Documentation

This directory contains comprehensive documentation for the Debug Suite WordPress plugin, covering all aspects of the advanced PSR-11 compliant dependency injection container system and plugin architecture.

## 📖 Documentation Index

### Core Documentation

- **[CONTAINER_SYSTEM_OVERVIEW.md](CONTAINER_SYSTEM_OVERVIEW.md)** - Complete overview and quick reference for the container system
- **[ENHANCED_DI_CONTAINER.md](ENHANCED_DI_CONTAINER.md)** - Main container system architecture and features
- **[CONTAINER_COMPLETE_GUIDE.md](CONTAINER_COMPLETE_GUIDE.md)** - Complete implementation guide with detailed examples
- **[CONTAINER_FEATURE_USAGE.md](CONTAINER_FEATURE_USAGE.md)** - Production-ready usage patterns and current implementations

### Specialized Components

- **[ENHANCED_AUTOWIRED_DEFINITION.md](ENHANCED_AUTOWIRED_DEFINITION.md)** - Advanced autowiring with parameter injection strategies
- **[SERVICE_LAYER_ARCHITECTURE.md](SERVICE_LAYER_ARCHITECTURE.md)** - Service layer pattern implementation
- **[REST_API_ARCHITECTURE.md](REST_API_ARCHITECTURE.md)** - REST API architecture with dependency injection
- **[PROVIDER.md](PROVIDER.md)** - Service provider patterns and lifecycle management
- **[HOOKABLE_INTERFACE.md](HOOKABLE_INTERFACE.md)** - WordPress hook integration interface

### Development Guidelines

- **[FEATURE_IMPLEMENTATION_QUICK_GUIDE.md](FEATURE_IMPLEMENTATION_QUICK_GUIDE.md)** - Quick guide for implementing new features
- **[TESTING.md](TESTING.md)** - Testing strategies and patterns
- **[DOCBLOCK.md](DOCBLOCK.md)** - Documentation standards and PHPDoc guidelines

## 🚀 Quick Start

### For New Developers

1. Start with **[CONTAINER_SYSTEM_OVERVIEW.md](CONTAINER_SYSTEM_OVERVIEW.md)** for a complete system overview
2. Review **[CONTAINER_FEATURE_USAGE.md](CONTAINER_FEATURE_USAGE.md)** for current production patterns
3. Use **[FEATURE_IMPLEMENTATION_QUICK_GUIDE.md](FEATURE_IMPLEMENTATION_QUICK_GUIDE.md)** for implementing new features

### For Container System Deep Dive

1. **[ENHANCED_DI_CONTAINER.md](ENHANCED_DI_CONTAINER.md)** - Core system architecture
2. **[ENHANCED_AUTOWIRED_DEFINITION.md](ENHANCED_AUTOWIRED_DEFINITION.md)** - Advanced autowiring features
3. **[CONTAINER_COMPLETE_GUIDE.md](CONTAINER_COMPLETE_GUIDE.md)** - Complete implementation details

### For API Development

1. **[SERVICE_LAYER_ARCHITECTURE.md](SERVICE_LAYER_ARCHITECTURE.md)** - Business logic separation
2. **[REST_API_ARCHITECTURE.md](REST_API_ARCHITECTURE.md)** - REST API patterns
3. **[PROVIDER.md](PROVIDER.md)** - Service registration patterns

## 🏗️ Current Implementation Status

The Debug Suite Container System is **fully implemented** and production-ready:

✅ **PSR-11 Compliant Container** - Complete with proper exception handling  
✅ **Advanced Autowiring** - Multiple parameter injection strategies  
✅ **Service Providers** - Enhanced lifecycle management with automatic hook registration  
✅ **Helper Functions** - Global utilities for easy container access  
✅ **WordPress Integration** - Seamless WP integration with Hookable interface  
✅ **Service Layer** - Business logic separation with ServiceResponse pattern  
✅ **REST API Integration** - Controllers with full dependency injection  
✅ **Testing Architecture** - Comprehensive testing patterns and mocking support

## 🔧 Current Services

### Implemented Services

- **FileLogsService** - Debug log file operations
- **FileManagerService** - File system operations  
- **SettingsService** - wp-config.php management

### API Controllers

- **FileLogsController** - Debug logs REST API
- **FileManagerController** - File manager REST API
- **SettingsController** - Settings REST API

### Service Providers

- **CoreServiceProvider** - WordPress core integration
- **AdminServiceProvider** - Admin area services
- **FrontendServiceProvider** - Public-facing services
- **AppServiceProvider** - Application services and API controllers

## 📚 Key Features Documented

### Container System

- PSR-11 compliance with proper exception handling
- Advanced autowiring with multiple parameter injection strategies
- Environment-aware service configuration
- Service decoration patterns
- Tagged services for event systems
- Container builder with fluent interface

### WordPress Integration  

- Automatic hook registration via Hookable interface
- Service provider lifecycle management
- WordPress-specific helper functions
- Admin and frontend service separation

### Development Patterns

- Service layer architecture for business logic separation
- REST API controllers with dependency injection
- ServiceResponse pattern for consistent error handling
- Testing patterns with mock dependencies
- Environment-specific configuration management

## 🤝 Contributing

When adding new features or modifying existing ones:

1. Follow the patterns documented in **[FEATURE_IMPLEMENTATION_QUICK_GUIDE.md](FEATURE_IMPLEMENTATION_QUICK_GUIDE.md)**
2. Use the Service Layer Pattern for business logic
3. Implement Hookable interface for WordPress hook registration
4. Follow PHPDoc standards from **[DOCBLOCK.md](DOCBLOCK.md)**
5. Add appropriate tests following **[TESTING.md](TESTING.md)** guidelines

## 📋 Documentation Standards

All documentation follows these standards:

- Clear, practical examples derived from actual codebase
- Production-ready patterns and implementations
- Comprehensive error handling and debugging information
- WordPress-specific integration guidelines
- Testing strategies and mock patterns

This documentation represents the current state of the Debug Suite plugin architecture and serves as the definitive guide for development and usage patterns.
