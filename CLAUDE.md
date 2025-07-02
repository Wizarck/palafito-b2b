# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Palafito B2B is a WordPress WooCommerce site specialized in wholesale B2B commerce for the Mexican market. The project consists of custom plugins, a child theme, and specific B2B customizations.

## Key Architecture

### Custom Plugin: `palafito-wc-extensions`
- **Main file**: `wp-content/plugins/palafito-wc-extensions/palafito-wc-extensions.php`
- **Primary class**: `Palafito_WC_Extensions` in `class-palafito-wc-extensions.php`
- **Key components**:
  - `Palafito_Checkout_Customizations`: Makes "Last Name" fields optional in billing/shipping
  - `Palafito_Packing_Slip_Settings`: Delivery date management for packing slips
  - Plugin hooks in `includes/plugin-hooks.php`
- **Initialization**: Uses `plugins_loaded` hook at priority 20 to ensure WooCommerce is available
- **HPOS Compatible**: Declares compatibility with WooCommerce High Performance Order Storage

### Child Theme: `palafito-child`
- **Parent theme**: Kadence Theme
- **Main file**: `wp-content/themes/palafito-child/functions.php`
- **Key features**:
  - Cross-sell product removal from cart
  - Checkout order notes field customization (14 rows)
  - Minimal class structure with `Palafito_Child_Theme`

### Third-Party Plugins (DO NOT MODIFY)
- **WholesaleX**: B2B pricing system (WORKING - DO NOT TOUCH)
- **WooCommerce PDF Invoices & Packing Slips**: PDF generation
- **Merge Orders**: Order consolidation functionality

## Development Commands

### Code Quality & Linting
```bash
# Lint custom plugin only
composer lint

# Fix custom plugin code standards
composer fix

# Lint all wp-content
composer lint:all

# Fix all wp-content code standards  
composer fix:all

# Pre-push validation (fix and lint all)
composer prepush
```

### Standards Configuration
- **PHPCS Config**: `phpcs.xml` - WordPress Coding Standards with custom exclusions
- **Excludes**: All third-party plugins, core files, and non-PHP files
- **Focus**: Only lints `palafito-wc-extensions` and `palafito-child` theme

## Important Business Logic

### PDF Document Date Management
- **Delivery Date Source**: Uses ONLY `_wcpdf_packing-slip_date` meta field
- **Synchronization**: Metabox, order table, and PDF all read/write to this single field
- **No cross-field logic**: Eliminated `_entregado_date` to avoid sync conflicts
- **Auto-generation**: If meta doesn't exist, current date is shown and saved automatically

### Order Management Features
- **Customer Notes Column**: Added to "My Account" orders table, truncated to 25 chars with tooltip
- **Note Merging**: When orders are merged, customer notes are preserved with "Nota original:" prefix
- **PDF Naming**: 
  - Packing slip: `[A-order_number] - [display_name].pdf`
  - Invoice: `[invoice_number] - [display_name].pdf`
- **Custom Order Statuses**: "Entregado" (Delivered) and "Facturado" (Invoiced) for B2B workflow
- **Automated Email Notifications**: Native WooCommerce emails for custom statuses

### Checkout Customizations
- **Optional Last Names**: Both billing and shipping last name fields are optional for B2B flow
- **Extended Order Notes**: 14-row textarea for detailed customer instructions

### Merge Orders Integration
- **Note Consolidation**: Combines invoice notes and customer notes with priority logic
- **CXXXXX Code Processing**: Extracts and sorts client codes from notes
- **Duplicate Detection**: Admin notifications when duplicate codes are found
- **Original Note Preservation**: Customer notes preserved with "Nota original:" prefix

### Plugin Architecture & Compatibility
- **PDF Plugin**: Uses white-label version of WooCommerce PDF IPS Pro
- **WholesaleX Integration**: B2B pricing system (DO NOT MODIFY - already working)
- **HPOS Compatibility**: Full support for WooCommerce High Performance Order Storage
- **Custom States Management**: Complete workflow from pending to completed via custom statuses

## File Structure to Understand

```
wp-content/
├── plugins/
│   ├── palafito-wc-extensions/     # Custom plugin - EDIT THIS
│   │   ├── class-palafito-wc-extensions.php
│   │   └── includes/
│   │       ├── class-palafito-checkout-customizations.php
│   │       ├── class-palafito-packing-slip-settings.php
│   │       └── plugin-hooks.php
│   └── wholesalex/                 # DO NOT MODIFY - Working B2B pricing
└── themes/
    ├── kadence/                    # Parent theme - DO NOT MODIFY
    └── palafito-child/             # Child theme - EDIT THIS
        ├── functions.php
        └── woocommerce/            # WooCommerce template overrides
```

## Security & WordPress Standards

- **ABSPATH Protection**: All PHP files check `defined( 'ABSPATH' )` to prevent direct access
- **Nonce Verification**: AJAX requests use proper nonce validation
- **Sanitization**: All user inputs are sanitized using WordPress functions
- **Capability Checks**: Admin functions check proper user capabilities

## Testing & Quality Assurance

### Required Checks Before Committing
1. Run `composer lint:all` - must pass without errors
2. Test checkout flow with optional last names
3. Verify PDF generation with correct dates
4. Confirm WholesaleX B2B pricing still works

### CI/CD Pipeline
- **GitHub Actions**: `.github/workflows/php-linting.yml`
- **Triggers**: Push/PR to master branch
- **Checks**: PHPCS validation, Composer security audit, PHP syntax

## Common Issues & Solutions

### Plugin Initialization
- **Problem**: Plugin loading before WooCommerce
- **Solution**: Use `plugins_loaded` hook at priority 20+
- **Location**: `palafito-wc-extensions.php:49`

### Date Field Synchronization
- **Problem**: Multiple date fields causing confusion
- **Solution**: Single source of truth `_wcpdf_packing-slip_date`
- **Location**: `class-palafito-packing-slip-settings.php`

### Code Standards Failures
- **Problem**: PHPCS WordPress standards violations
- **Solution**: Run `composer fix:all` before `composer lint:all`
- **Config**: `phpcs.xml` excludes third-party code

### Content Security Policy (CSP) Issues
- **Problem**: Hosting blocks inline CSS styles from Kadence theme
- **Solution**: Disable dynamic CSS via filter in child theme
- **Implementation**: `add_filter( 'kadence_dynamic_css', '__return_false' );`

### Mixed Content Warnings
- **Problem**: HTTP resources loaded on HTTPS site
- **Solution**: Database URL conversion script executed
- **Status**: All URLs converted from HTTP to HTTPS

### PDF Plugin License Restrictions
- **Problem**: Pro plugin showing license warnings
- **Solution**: White-label modification removing all license checks
- **Status**: Plugin operates without restrictions

## Mexican Market Specifics

- **Currency**: MXN (Mexican Peso)
- **Language**: Spanish (es_ES) - translation files in `/languages/`
- **B2B Focus**: Wholesale pricing, bulk orders, custom invoicing
- **Address Format**: NIF fields for tax identification

## Critical Business Rules & Workflows

### Order Status Workflow
```
Pending → Processing → Entregado (Delivered) → Facturado (Invoiced) → Completed
```

### Email Automation
- **Status Change**: Automatic emails sent on status transitions
- **PDF Attachments**: Packing slip for "Entregado", Invoice for "Facturado"
- **Templates**: Custom email templates in `wp-content/plugins/palafito-wc-extensions/templates/emails/`

### Address Formatting in PDFs
- **Customer Address**: Name + Surname, NIF (billing only), Address, Postal Code + City - Country, Phone
- **Store Address**: NIF, Address, Postal Code + City - Country, Email (no company name repetition)
- **Spain Suffix**: Automatic "- España" addition for Spanish addresses only

### Code Quality Standards
- **PHPCS Compliance**: All custom code must pass WordPress Coding Standards
- **Security**: ABSPATH checks, nonce verification, input sanitization
- **Documentation**: Proper DocBlock comments for all functions and classes

## Development Best Practices

### Before Each Commit
1. Run `composer fix:all` to auto-fix code standards
2. Run `composer lint:all` to verify compliance
3. Test critical B2B functionality (checkout, PDF generation, status changes)
4. Update documentation if business logic changes

### Hosting Considerations
- **Provider**: 1&1 IONOS with PHP 7.4.9
- **CSP Restrictions**: Inline styles blocked, use external CSS files
- **File Permissions**: Direct access to theme CSS files may be restricted

## Current Status & Pending Tasks

### ✅ Completed Features
- **Core B2B Functionality**: Custom order statuses, email automation, PDF generation
- **Checkout Optimization**: Optional last names, 14-row order notes, payment method automation
- **PDF Management**: White-label Pro plugin, automated attachments, delivery date tracking
- **Order Management**: Custom columns, note merging, CXXXXX code processing
- **Code Quality**: 100% PHPCS compliance, automated testing via GitHub Actions
- **Documentation**: README.md, CONTEXT.md (deprecated), and this CLAUDE.md file

### 🔄 Pending Items (from TODO.md)
- **Security Hardening**: File edit restrictions, XML-RPC disabling, environment variables
- **UI Improvements**: Cart icon routing, hero/banner color customization
- **Development Workflow**: Branch strategy, PR rules, deployment notifications
- **Legacy Data**: Review old orders for missing `_wcpdf_packing-slip_date` meta

### 🚨 Current Issues (from README.md)
- **Design Inconsistencies**: Fonts not matching Kadence theme
- **Button Behavior**: Strange hover behavior
- **Diagnosis Available**: TODO-DESIGN-DIAGNOSIS.md with 10 verification points

### 🛡️ Production Environment
- **PHP Version**: 7.4.9 (production) vs 7.4+ (development requirement)
- **WordPress**: 6.4+ required
- **WooCommerce**: 8.0+ required
- **Database**: MySQL 5.7+ required