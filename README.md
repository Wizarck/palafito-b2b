# Palafito B2B - Complete B2B E-commerce Solution

**Version:** 2.2.0
**Status:** ✅ **PRODUCTION READY + ROADMAP EXTENDED**
**Last Updated:** 17 Julio 2025

## 🚀 Project Overview

Palafito B2B is a **complete, production-ready B2B e-commerce solution** built on WordPress + WooCommerce with advanced PDF automation, intelligent delivery date management, custom order statuses, email personalization with customer codes, ultra-aggressive generation control, and automated CI/CD deployment. The system is **100% operational** with an extended roadmap including order notes, WhatsApp integration, and E2E testing.

### ✨ Key Features

- 🎯 **Automated PDF Generation**: 4 trigger scenarios for packing slips + Order notes (planned)
- 📅 **Triple-Sync Date Management**: Bulletproof delivery & invoice dates
- 🔄 **Custom Order States**: "Entregado" and "Facturado" with workflows
- 📄 **Optimized PDF Templates**: Perfect positioning and unified structure
- 🏛️ **Enhanced Admin Columns**: Real-time data with smart fallbacks
- 🚀 **GitHub Actions CI/CD**: Automated testing and deployment + E2E testing (planned)
- 💻 **PHPCS Compliant**: WordPress/WooCommerce coding standards
- 📧 **Email Personalization**: Customer codes in email titles (C12345)
- 🛡️ **Ultra Aggressive Control**: Absolute control over PDF auto-generation
- 🤖 **WhatsApp Integration**: Complete ordering system via WhatsApp (planned)
- 🧪 **E2E Testing Framework**: Playwright automated testing (planned)

## 🏗️ System Architecture

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   WordPress     │    │   WooCommerce   │    │   Custom Plugin │
│   Core 6.4+     │◄──►│   8.0+ HPOS     │◄──►│   palafito-wc   │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                       │                       │
         ▼                       ▼                       ▼
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Kadence       │    │   PDF Plugin    │    │   GitHub        │
│   Theme + PDF   │    │   + Pro         │    │   Actions       │
│   Templates     │    │   Engine        │    │   CI/CD + E2E   │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                       │                       │
         ▼                       ▼                       ▼
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   WhatsApp      │    │   Order Notes   │    │   E2E Testing   │
│   Business API  │    │   System        │    │   Framework     │
│   (planned)     │    │   (planned)     │    │   (planned)     │
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

### 🎯 Core Components

| Component | Status | Description |
|-----------|--------|-------------|
| **Custom Plugin** | ✅ Active | `palafito-wc-extensions` - Core B2B functionality |
| **PDF Templates** | ✅ Optimized | Unified structure with perfect positioning |
| **Date System** | ✅ Triple-Sync | Delivery & invoice dates with redundancy |
| **Order States** | ✅ Operational | Custom "entregado" and "facturado" workflows |
| **CI/CD Pipeline** | ✅ Automated | GitHub Actions with IONOS deployment |
| **Email Personalization** | ✅ Operational | Customer codes in email titles |
| **Ultra Control System** | ✅ Active | Aggressive PDF generation control |
| **Order Notes System** | 🔄 Planned | PDF notes for new orders (2-3 weeks) |
| **WhatsApp Integration** | 🔄 Planned | Complete ordering via WhatsApp (4-6 weeks) |
| **E2E Testing** | 🔄 Planned | Playwright automated testing (3-4 weeks) |

## 📦 Installation & Setup

### Prerequisites

```bash
- WordPress 6.4+
- WooCommerce 8.0+
- PHP 8.1+
- Composer (for development)
- Git (for version control)
- Node.js 18+ (for E2E testing - planned)
- WhatsApp Business API access (for WhatsApp features - planned)
```

### 1. Clone Repository

```bash
git clone https://github.com/Wizarck/palafito-b2b.git
cd palafito-b2b
```

### 2. Install Dependencies

```bash
composer install
npm install  # For E2E testing (planned)
```

### 3. Configure WordPress

```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('PALAFITO_ENV', 'development'); // or 'production'

// WhatsApp Configuration (planned)
define('WHATSAPP_ACCESS_TOKEN', 'your_access_token');
define('WHATSAPP_PHONE_NUMBER_ID', 'your_phone_number_id');
define('WHATSAPP_VERIFY_TOKEN', 'your_verify_token');
define('PALAFITO_WHATSAPP_ENABLED', true);
```

### 4. Activate Required Plugins

1. **WooCommerce** (base e-commerce)
2. **WooCommerce PDF Invoices & Packing Slips** (PDF generation)
3. **WooCommerce PDF Invoices & Packing Slips Pro** (advanced features)
4. **Palafito WC Extensions** (custom functionality)

### 5. Configure PDF Plugin

Navigate to `WooCommerce > PDF Invoices` and configure:
- Enable invoice and packing slip generation
- Set up email attachments
- Configure document numbering

## 🎯 Feature Documentation

### 📄 Automated PDF Generation

The system automatically generates PDF documents in **multiple scenarios**:

#### Current Triggers (✅ Operational):
```php
1. Manual metabox date change (admin panel)
2. Manual "Generate PDF" button click
3. Order status change to "entregado" → Packing slip PDF
4. Order status change to "facturado"/"completed" without existing date → Packing slip PDF
```

#### Planned Triggers (🔄 Development):
```php
5. Order status change to "processing"/"on-hold" → Order note PDF (no delivery date)
6. WhatsApp order confirmation → Order note PDF
7. Auto-print trigger for order notes
```

**Technical Implementation:**
```php
// Central PDF generation function
public static function generate_packing_slip_pdf($order) {
    $packing_slip = wcpdf_get_document('packing-slip', $order, true);
    $pdf_file = $packing_slip->get_pdf();
    $order->add_order_note('Albarán automaticamente generado.');
    return true;
}

// Planned: Order notes generation
public static function generate_order_note_pdf($order) {
    $order_note = wcpdf_get_document('order-note', $order, true);
    $pdf_file = $order_note->get_pdf();
    $order->add_order_note('Nota de pedido generada automáticamente.');
    return true;
}
```

### 📅 Delivery Date Management

**Triple-Sync Technology** ensures date consistency:

| Method | Field | Priority | Usage |
|--------|-------|----------|--------|
| WC Meta | `_wcpdf_packing-slip_date` | Primary | Admin display |
| Direct DB | `wp_postmeta` table | Fallback | Data integrity |
| PDF Sync | PDF document object | Validation | Template rendering |

**Auto-Generation Rules:**
- ✅ State → "entregado": Always set date
- ✅ Manual metabox: Instant PDF generation
- ✅ State → "facturado" without date: Create date + PDF
- ❌ Non-entregado states: Block date setting

### 🛡️ Ultra Aggressive Control System

**Multiple layers of protection** to prevent premature PDF generation:

```php
// Layer 1: Force disable auto-generation
public static function force_disable_packing_slip_auto_generation($settings, $document_type) {
    if ('packing-slip' === $document_type) {
        $settings['auto_generate'] = 0;
    }
    return $settings;
}

// Layer 2: Replace PRO plugin hooks
public static function ultra_aggressive_pro_packing_slip_block() {
    remove_action('woocommerce_order_status_changed',
        array(WPO_WCPDF_Pro()->functions, 'generate_documents_on_order_status'), 7);
    add_action('woocommerce_order_status_changed',
        array(__CLASS__, 'custom_pro_document_generation'), 7, 4);
}

// Layer 3: Controlled generation
public static function custom_pro_document_generation($order_id, $old_status, $new_status, $order) {
    $allowed_statuses = array('entregado', 'facturado', 'completed');
    if (!in_array($new_status, $allowed_statuses, true)) {
        return; // Block generation
    }
    // Continue with controlled generation...
}
```

### 📧 Email Personalization

**Customer codes in email titles:**

```php
// Extract customer codes from order notes
public static function extract_customer_codes_from_notes($notes) {
    $pattern = '/C\d{5}/'; // C + exactly 5 digits
    preg_match_all($pattern, $notes, $matches);
    return array_unique($matches[0]);
}

// Customize email subject
public static function customize_entregado_email_subject($subject, $order) {
    $customer_note = $order->get_customer_note();
    $codes = self::extract_customer_codes_from_notes($customer_note);

    if (!empty($codes)) {
        $codes_string = implode(' ', $codes);
        $subject = str_replace('ha sido entregado',
            '/ ' . $codes_string . ' ha sido entregado', $subject);
    }

    return $subject;
}
```

**Examples:**
- Input: "Feria: C00303 - RBF - Benidorm"
- Output: "Tu pedido #2514 / C00303 ha sido entregado"

### 🔄 Custom Order States

#### Custom States Added:
```php
'wc-entregado'  => 'Entregado'   // Delivered status
'wc-facturado'  => 'Facturado'   // Invoiced status
```

#### Extended Workflow:
```
pending → processing → entregado → facturado → completed
               ↘             ↗
                  on-hold
```

#### Enhanced Workflow with Planned Features:
```
1. New Order (Web/WhatsApp) → processing/on-hold
   → Order note PDF generated
   → Confirmation email with note attached

2. Admin marks "entregado"
   → Delivery date + Packing slip PDF
   → Personalized email with customer code

3. Admin marks "facturado"
   → Invoice date + Invoice PDF
   → Invoice email notification
```

## 🆕 Planned Features

### 📋 Order Notes System (2-3 weeks)

**Objective:** Create PDF documents similar to packing slips but without delivery dates for new order confirmations.

#### Features:
- **Auto-generation**: For processing/on-hold statuses
- **Email integration**: Attached to order confirmation emails
- **Auto-print**: Optional automatic printing
- **Template**: Similar to packing slip without delivery date

#### Implementation:
```php
// New document class
class WPO_WCPDF_Order_Note extends WPO_WCPDF_Document

// Registration
add_filter('wpo_wcpdf_document_classes', 'register_order_note_document');

// Auto-generation trigger
add_action('woocommerce_order_status_processing', 'generate_order_note_pdf');

// Email attachment
add_filter('woocommerce_email_attachments', 'attach_order_note_to_emails');
```

### 🤖 WhatsApp Integration System (4-6 weeks)

**Objective:** Complete ordering system via WhatsApp with bidirectional communication.

#### Components:

##### 1. WhatsApp Message Parser
```php
class Palafito_WhatsApp_Parser {
    public function parse_order_message($message) {
        // Extract products, quantities, customer info
        // Validate against WooCommerce catalog
        // Return structured order data
    }
}
```

##### 2. Order Creation from WhatsApp
```php
class Palafito_WhatsApp_Order_Creator {
    public function create_order_from_whatsapp($parsed_data, $phone_number) {
        // Create WooCommerce order
        // Set customer data based on phone
        // Apply B2B pricing
        // Send confirmation back to WhatsApp
    }
}
```

##### 3. WhatsApp Business API Integration
```php
class Palafito_WhatsApp_API {
    public function send_message($to, $message);
    public function receive_webhook($webhook_data);
    public function verify_webhook($signature, $payload);
}
```

#### Workflow:
```
Customer WhatsApp Message → Bot Parser → Product Validation →
Draft Order → Customer Confirmation → WooCommerce Order →
Order Note PDF → WhatsApp Confirmation
```

### 🧪 E2E Testing Framework (3-4 weeks)

**Objective:** Comprehensive automated testing for the complete B2B workflow.

#### Stack:
- **Framework**: Playwright + PHPUnit
- **Coverage**: Full B2B workflow automation
- **Reports**: Screenshots and automated reporting
- **CI/CD**: Integrated with GitHub Actions

#### Test Scenarios:

##### 1. Complete Order Lifecycle
```javascript
test('Full B2E workflow', async ({ page }) => {
    // 1. Create order via frontend
    // 2. Verify order in admin
    // 3. Change status to processing
    // 4. Verify order note PDF generation
    // 5. Change status to entregado
    // 6. Verify packing slip PDF + date
    // 7. Change status to facturado
    // 8. Verify invoice generation
    // 9. Verify email notifications
    // 10. Verify customer codes in emails
});
```

##### 2. WhatsApp Integration Testing
```javascript
test('WhatsApp order creation', async ({ page }) => {
    // Simulate WhatsApp webhook
    // Verify order parsing
    // Test order creation
    // Validate PDF generation
    // Check confirmation message
});
```

##### 3. Ultra Aggressive Control Testing
```javascript
test('PDF generation control', async ({ page }) => {
    // Test blocking in processing state
    // Verify manual generation works
    // Test auto-generation in entregado
    // Validate ultra aggressive blocking
});
```

### 📊 Monitoring Dashboard (2-3 weeks)

**Objective:** Real-time monitoring and analytics for the B2B system.

#### Features:
- Order processing metrics
- PDF generation statistics
- WhatsApp integration activity
- System health monitoring
- Customer engagement tracking
- Revenue analytics
- Error logging and alerts

## 🚀 Development Workflow

### Pre-Push Requirements

```bash
# 1. Install dependencies
composer install
npm install  # For E2E testing (planned)

# 2. Run code linting
composer lint

# 3. Auto-fix when possible
composer lint:fix

# 4. Run unit tests
composer test

# 5. Run E2E tests (planned)
npm run test:e2e

# 6. Commit and push
git add .
git commit -m "descriptive message"
git push origin master  # Triggers GitHub Actions
```

### GitHub Actions Pipeline

**Enhanced Workflow**: `.github/workflows/deploy.yml`

```yaml
name: Deploy to Production with E2E Testing

on:
  push:
    branches: [ master ]
  pull_request:
    branches: [ master ]

jobs:
  test-and-deploy:
    runs-on: ubuntu-latest

    steps:
    - uses: actions/checkout@v3

    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.1'

    - name: Install dependencies
      run: composer install --no-dev --optimize-autoloader

    - name: Run PHPCS
      run: composer lint

    - name: Run Unit Tests
      run: composer test

    - name: Setup Node.js for E2E
      uses: actions/setup-node@v3
      with:
        node-version: '18'

    - name: Install Playwright
      run: npm install @playwright/test

    - name: Run E2E Tests
      run: npm run test:e2e

    - name: Deploy to IONOS
      if: github.ref == 'refs/heads/master'
      # ... deployment steps
```

### Testing Strategy

#### Unit Tests (Current)
```php
class TestPalafitoFunctionality extends WP_UnitTestCase {
    public function test_delivery_date_setting() {
        $order = wc_create_order();
        Palafito_WC_Extensions::set_delivery_date_with_triple_sync($order);
        $this->assertNotEmpty($order->get_meta('_wcpdf_packing-slip_date'));
    }
}
```

#### E2E Tests (Planned)
```javascript
// playwright.config.js
module.exports = {
  testDir: './tests/e2e',
  use: {
    baseURL: 'http://localhost/palafito-b2b',
    headless: true,
    screenshot: 'only-on-failure',
  },
  projects: [
    { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
    { name: 'firefox', use: { ...devices['Desktop Firefox'] } },
  ],
};
```

## 🔧 Configuration

### Required Settings

#### PDF Plugin Configuration:
```php
// Auto-configured by plugin
'display_date' => 'document_date'     // Invoice dates
'display_number' => 'invoice_number'  // Invoice numbers
'display_date' => 1                   // Packing slip dates
'display_number' => 'order_number'    // Packing slip numbers
```

#### WordPress Configuration:
```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('PALAFITO_ENV', 'production');

// WhatsApp Configuration (planned)
define('WHATSAPP_ACCESS_TOKEN', 'your_access_token');
define('WHATSAPP_PHONE_NUMBER_ID', 'your_phone_number_id');
define('WHATSAPP_VERIFY_TOKEN', 'your_verify_token');
```

### Email Configuration

Custom order status emails automatically configured:
- **Customer Entregado**: Sent when order marked as delivered (with customer codes)
- **Customer Facturado**: Sent when order marked as invoiced
- **Order Confirmation**: With order note PDF attached (planned)
- **WhatsApp Notifications**: Bidirectional confirmations (planned)

## 📊 Monitoring & Maintenance

### Health Checks

```php
// Enhanced system status verification
public static function extended_health_check() {
    $checks = array(
        'pdf_plugin' => function_exists('wcpdf_get_document'),
        'custom_states' => post_type_exists('shop_order'),
        'templates' => self::verify_all_templates(),
        'ultra_control' => self::verify_aggressive_blocking(),
        'email_personalization' => self::test_email_customization(),
        'whatsapp_api' => self::test_whatsapp_connection(), // planned
        'e2e_tests' => self::verify_test_environment(), // planned
        'order_notes' => class_exists('Palafito_Order_Notes'), // planned
    );

    return array_filter($checks);
}
```

### Log Locations

| Log Type | Location | Purpose |
|----------|----------|---------|
| **WordPress** | `wp-content/debug.log` | General errors |
| **Plugin** | Same file with `[PALAFITO]` prefix | Custom logging |
| **GitHub Actions** | Repository Actions tab | CI/CD pipeline |
| **E2E Tests** | `tests/e2e/test-results/` | Test results (planned) |
| **WhatsApp** | `wp-content/logs/whatsapp.log` | WhatsApp API (planned) |

### Performance Metrics

- **PDF Generation**: ~2-3 seconds per document
- **Order Note Creation**: ~1-2 seconds per note (planned)
- **Date Sync**: Real-time with triple redundancy
- **Admin Columns**: Optimized queries with caching
- **Deploy Time**: ~30 seconds via GitHub Actions
- **E2E Test Suite**: ~5-10 minutes complete run (planned)
- **WhatsApp Response**: ~500ms per message (planned)

## 📋 Implementation Roadmap

### Phase 1: Order Notes System (Next 2-3 weeks)
- [ ] Research PDF plugin extensibility
- [ ] Create order notes document class
- [ ] Implement template system
- [ ] Integrate with email system
- [ ] Add auto-print functionality
- [ ] Test with existing workflow

### Phase 2: E2E Testing Framework (Next 3-4 weeks)
- [ ] Setup Playwright framework
- [ ] Create core test scenarios
- [ ] Integrate with CI/CD pipeline
- [ ] Add visual regression testing
- [ ] Create automated reporting
- [ ] Test WhatsApp integration scenarios

### Phase 3: WhatsApp Integration (Next 4-6 weeks)
- [ ] Research WhatsApp Business API
- [ ] Implement message parsing
- [ ] Create order creation system
- [ ] Build bidirectional communication
- [ ] Add comprehensive testing
- [ ] Integration with order notes system

### Phase 4: Monitoring Dashboard (Next 2-3 weeks)
- [ ] Create dashboard interface
- [ ] Implement metrics collection
- [ ] Add real-time monitoring
- [ ] Create alert system
- [ ] Build reporting features

## 🛡️ Security Features

### Current Security (✅ Implemented)
```php
// Input validation
wp_verify_nonce($_POST['nonce'], 'palafito_action')
sanitize_text_field(wp_unslash($_POST['data']))
current_user_can('manage_woocommerce')

// SQL injection prevention
$wpdb->prepare("SELECT * FROM {$wpdb->postmeta} WHERE post_id = %d", $order_id);
```

### Planned Security (🔄 Development)
```php
// WhatsApp webhook verification
public function verify_whatsapp_webhook($signature, $payload) {
    $expected = hash_hmac('sha256', $payload, WHATSAPP_VERIFY_TOKEN);
    return hash_equals($signature, $expected);
}

// E2E test data isolation
public function setUp(): void {
    // Create isolated test environment
    // Mock sensitive data
    // Setup test-specific configurations
}
```

## 🎯 Production Deployment

### Current Status: ✅ **FULLY DEPLOYED + EXTENDED ROADMAP**

- **Server**: IONOS Hosting
- **Domain**: Production URL configured
- **SSL**: Active and configured
- **Backup**: Automated pre-deployment backups
- **Monitoring**: GitHub Actions pipeline monitoring
- **E2E Testing**: Framework in development
- **WhatsApp Integration**: Planning phase

### Enhanced Deployment Process

```bash
1. Developer pushes to master
2. GitHub Actions triggers automatically
3. Code passes PHPCS validation
4. Unit tests execute and pass
5. E2E tests execute and pass (planned)
6. WhatsApp integration tests (planned)
7. Secure SSH deployment to IONOS
8. Server executes web_update_from_repo.sh
9. Automatic backup created
10. Code updated and verified
11. Health checks performed
12. Deployment confirmation
```

## 📚 Documentation Files

| File | Purpose | Audience | Status |
|------|---------|----------|--------|
| **README.md** | Project overview & roadmap | All users | ✅ Updated |
| **CONTEXT.md** | Complete system documentation | Technical team | ✅ Updated |
| **CLAUDE.md** | Technical implementation details | Developers | ✅ Updated |
| **composer.json** | Dependencies & scripts | Developers | ✅ Current |
| **phpcs.xml** | Code standards configuration | Developers | ✅ Current |
| **playwright.config.js** | E2E testing configuration | QA/Developers | 🔄 Planned |
| **TODO.md** | Task management | Team | ✅ Active |

## 🎯 Use Cases

### Current B2B Workflow (✅ Operational)

```
1. Customer places order → Status: processing
   → Order note PDF generated (planned)
   → Confirmation email with note (planned)

2. Admin prepares shipment → Status: entregado
   → Auto-generates delivery date
   → Creates packing slip PDF
   → Sends personalized email with customer code (C12345)

3. Admin processes invoice → Status: facturado
   → Auto-generates invoice date
   → Sends invoice notification email

4. Order completion → Status: completed
```

### Enhanced WhatsApp Workflow (🔄 Planned)

```
1. Customer sends WhatsApp message: "Pedido: 2x Producto A, 1x Producto B"
2. Bot parses message and validates products
3. Bot creates draft order and sends confirmation
4. Customer confirms via WhatsApp
5. Order created in WooCommerce (processing status)
6. Order note PDF generated automatically
7. Confirmation sent to customer via WhatsApp
8. Traditional B2B workflow continues...
```

### Administrative Tasks (✅ Current + 🔄 Enhanced)

- **Mass Status Updates**: Select multiple orders, bulk change status
- **PDF Regeneration**: Manual trigger for any order
- **Date Management**: View/edit delivery and invoice dates
- **Report Generation**: Export order data with custom columns
- **WhatsApp Monitoring**: Track WhatsApp order activity (planned)
- **E2E Test Execution**: Run automated test suites (planned)
- **System Health Dashboard**: Monitor all components (planned)

## 🔮 Future Enhancements

### Immediate Roadmap (Next 6 months)

1. **Order Notes System**: PDF confirmations for new orders
2. **E2E Testing Framework**: Comprehensive automated testing
3. **WhatsApp Integration**: Complete ordering via WhatsApp
4. **Monitoring Dashboard**: Real-time system metrics

### Long-term Vision (6-12 months)

1. **Advanced Analytics**: AI-powered business insights
2. **API Integration**: REST endpoints for external systems
3. **Mobile App**: Customer self-service portal
4. **Inventory Integration**: Real-time stock management
5. **Multi-language Support**: International B2B expansion
6. **Advanced Automation**: Machine learning for order processing

### Technical Improvements

- Implement comprehensive caching layer
- Add automated integration testing
- Optimize database queries further
- Implement real-time monitoring dashboard
- Add progressive web app capabilities

## 📞 Support & Contact

### System Status: ✅ **PRODUCTION READY + ACTIVE DEVELOPMENT**

**Version**: 2.2.0
**Stability**: 100% Operational
**Development**: Active roadmap
**Last Health Check**: 17 Julio 2025

### Component Status Overview

| Component | Status | Notes |
|-----------|--------|-------|
| **Core B2B System** | ✅ 100% Operational | All features working |
| **PDF Generation** | ✅ 100% Functional | 4 triggers + ultra control |
| **Email Personalization** | ✅ 100% Working | Customer codes active |
| **Ultra Aggressive Control** | ✅ 100% Blocking | No premature generation |
| **Order Notes System** | 🔄 In Development | 2-3 weeks timeline |
| **WhatsApp Integration** | 🔄 Planning Phase | 4-6 weeks timeline |
| **E2E Testing Framework** | 🔄 Setup Phase | 3-4 weeks timeline |
| **Monitoring Dashboard** | 🔄 Design Phase | 2-3 weeks timeline |

### Quick Support Checklist

1. **PDF Issues**: Check `wp-content/debug.log` for `[PALAFITO]` entries
2. **Date Problems**: Verify order status and enhanced logic
3. **Deploy Issues**: Check GitHub Actions tab in repository
4. **Code Standards**: Run `composer lint` before push
5. **Email Personalization**: Verify customer note formats (CXXXXX)
6. **Ultra Control**: Check blocking logs in debug.log
7. **WhatsApp Issues**: Check webhook verification (when implemented)
8. **E2E Test Failures**: Review test results and screenshots (when implemented)

### Development Support

```bash
# Development commands
composer install          # Install PHP dependencies
npm install               # Install Node.js dependencies (planned)
composer lint             # Check code standards
composer test             # Run unit tests
npm run test:e2e          # Run E2E tests (planned)
npm run test:whatsapp     # Test WhatsApp integration (planned)

# Debugging
tail -f wp-content/debug.log                    # Monitor logs
grep "[PALAFITO]" wp-content/debug.log          # Filter plugin logs
grep "ULTRA" wp-content/debug.log               # Check control system
```

### Technical Specifications

- **WordPress**: 6.4+ (HPOS compatible)
- **WooCommerce**: 8.0+
- **PHP**: 8.1+
- **Node.js**: 18+ (for E2E testing)
- **Database**: MySQL 5.7+
- **Server**: IONOS Hosting
- **CI/CD**: GitHub Actions
- **Testing**: PHPUnit + Playwright
- **WhatsApp**: Business API (planned)

---

## 🎉 **SYSTEM STATUS: FULLY OPERATIONAL + EXTENDED ROADMAP**

The Palafito B2B system is **production-ready** and **100% functional** with all core features implemented, tested, and deployed. The automated CI/CD pipeline ensures continuous delivery with zero downtime.

**Current State**: All production features operational
**Development State**: Active roadmap with 4 major feature additions
**Timeline**: 6 months for complete roadmap implementation
**Support**: Comprehensive documentation and monitoring

**Ready for continuous production use and exciting feature expansion.**

---

*This documentation reflects the current production state and planned enhancements. All timelines are estimates based on current development capacity.*
