# Palafito WC Extensions

A custom WordPress plugin for WooCommerce B2B extensions and customizations.

## Description

This plugin provides custom WooCommerce functionality for B2B (Business-to-Business) operations, including checkout customizations and order management features.

## Features

### Checkout Customizations
- **Company Type Field**: Adds a dropdown for selecting business type (Wholesale, Retail, Distributor)
- **Business Name Field**: Adds a required business name field for B2B customers
- **Custom Field Storage**: Saves custom B2B fields to order meta for admin reference

### Order Management
- Custom order processing logic
- B2B-specific order status handling
- Integration with existing B2B pricing systems (wholesalex plugin)

## Installation

1. Upload the plugin files to `/wp-content/plugins/palafito-wc-extensions/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure any settings in WooCommerce > Settings > Palafito Extensions

## Requirements

- WordPress 5.0 or higher
- WooCommerce 5.0 or higher
- PHP 7.4 or higher

## Development

### Running Tests

```bash
# Install dependencies
composer install

# Run tests
vendor/bin/phpunit
```

### Code Standards

The plugin follows WordPress coding standards. Use PHPCS to check code quality:

```bash
vendor/bin/phpcs --standard=WordPress .
```

## Changelog

### 1.0.0
- Initial release
- B2B checkout customizations
- Custom field management
- PHPUnit test suite

## License

GPL v2 or later 