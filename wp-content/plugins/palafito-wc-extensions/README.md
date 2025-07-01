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

---

## 📧 Custom Order Status Emails: Entregado & Facturado

### Overview
- Native WooCommerce emails are sent automatically when an order is marked as **Entregado** or **Facturado**.
- PDF attachments (packing slip or invoice) are included if configured in the PDF Invoices & Packing Slips plugin.

### How it works
- **Triggering:**
  - Emails are sent when the order status changes to "Entregado" or "Facturado".
  - This works via:
    - Custom admin buttons (bulk or single order view)
    - Manual status change in the order edit screen
    - Any automated status transition
- **Email Classes:**
  - `WC_Email_Customer_Entregado` and `WC_Email_Customer_Facturado` are registered as native WooCommerce emails.
  - Each class hooks into the corresponding status change and sends the email only if enabled and with a valid recipient.
- **PDF Attachments:**
  - The plugin integrates with PDF Invoices & Packing Slips.
  - In **Facturas PDF > Documentos**, you can select which emails should include the packing slip or invoice as an attachment.
  - The plugin respects this configuration: PDFs are only attached if the email is checked in the admin settings.
- **Document Generation:**
  - Packing slip is generated only for "Entregado" and "Facturado".
  - Invoice is generated only for "Facturado".
- **Robustness:**
  - The trigger works for all admin actions (buttons, bulk, manual) and is not duplicated.
  - All logic is PHPCS-compliant and fully integrated with WooCommerce and the PDF plugin.

### Example Workflow
1. Admin marks an order as "Entregado" (via button, bulk, or manual change)
2. The plugin triggers the custom email and attaches the packing slip if configured
3. Later, admin marks the order as "Facturado"
4. The plugin triggers the custom email and attaches the invoice if configured

---

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

### [2024-xx-xx] Nuevos estados personalizados de pedido
- Añadidos los estados 'Entregado' y 'Facturado' a WooCommerce.
- Flujos de pedido:
  - **B2B:** Pendiente de pago → Procesando → Entregado → Facturado → Completado
  - **B2C:** Pendiente de pago → Procesando → Entregado → Completado
- Finalidad:
  - **Entregado:** Pedido entregado físicamente, pendiente de facturación/cobro.
  - **Facturado:** Pedido incluido en factura consolidada, pendiente de pago.
- Los estados aparecen en el admin, filtros y acciones masivas, y se comportan como los nativos.

## License

GPL v2 or later 