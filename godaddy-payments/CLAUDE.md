# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is **GoDaddy Payments for WooCommerce** (formerly known as Poynt), a WordPress plugin that integrates GoDaddy's payment processing system with WooCommerce. The plugin enables merchants to accept credit/debit card payments and supports physical terminals for in-person transactions.

**Key Facts:**
- PHP namespace: `GoDaddy\WooCommerce\Poynt`
- Plugin text domain: `godaddy-payments`
- Requires: PHP 7.4+, WordPress 5.6+, WooCommerce 4.0+
- Built on SkyVerge WooCommerce Plugin Framework v5.15.12
- Supported countries: US (USD), CA (CAD)

## Development Setup

```bash
composer install
pre-commit install
npm install
npx sake compile
```

If cloned outside your WordPress installation, symlink into `wp-content/plugins/`.

## Common Commands

### Asset Compilation
```bash
npx sake compile              # Re-compile all plugin assets
npx sake compile:scripts      # Re-compile JavaScript only
npx sake compile:styles       # Re-compile CSS only
npx sake bundle              # Bundle external React dependencies
```

### Testing
```bash
vendor/bin/codecept run unit  # Run unit tests via Codeception
composer test                 # Run PHPUnit tests
vendor/bin/phpunit            # Run PHPUnit directly
```

### Code Quality
```bash
composer cs                   # Run PHP Code Sniffer
composer cbf                  # Run PHP Code Beautifier and Fixer
```

Pre-commit hooks run `php-cs-fixer` automatically on staged PHP files.

### Deployment
```bash
npx sake deploy              # Deploy new version to WordPress.org
```

**Important deployment prerequisites:**
- Set `WP_SVN_USER="Godaddy"` environment variable
- Temporarily disable the GitHub main branch protection rule
- Update version in: `readme.txt` (stable tag + changelog), `godaddy-payments.php` headers, and `Plugin::VERSION` constant
- Version format must be `x.y.z-dev.n` before deployment

## Architecture

### Plugin Bootstrap Flow

1. **godaddy-payments.php** - Plugin entry point with `GD_Poynt_For_WooCommerce_Loader` singleton
   - Validates environment (PHP/WordPress/WooCommerce versions)
   - Loads SkyVerge framework classes
   - Includes Composer autoloader
   - Calls `poynt_for_woocommerce()` helper function

2. **src/Functions.php** - Contains `poynt_for_woocommerce()` helper that returns `Plugin::instance()`

3. **src/Plugin.php** - Main plugin class extending `Framework\SV_WC_Payment_Gateway_Plugin`
   - Registers payment gateways (Credit Card and Pay In Person)
   - Manages plugin lifecycle, webhooks, REST API, and synchronization

### Payment Gateways

Two gateway implementations:

- **CreditCardGateway** (`src/Gateways/CreditCardGateway.php`) - Online credit/debit card payments
  - Extends `Framework\SV_WC_Payment_Gateway_Direct`
  - Supports: Amex, Diners Club, Discover, JCB, Mastercard, UnionPay, Visa
  - Integrates with WooCommerce Blocks via `CreditCardCheckoutBlockIntegration`

- **PayInPersonGateway** (`src/Gateways/PayInPersonGateway.php`) - Physical terminal (Smart Terminal) payments
  - Integrates with local pickup/delivery shipping methods

### API Layer (`src/API/`)

**Base Classes:**
- `API.php` - Base API handler with token management
  - Production: `https://services.poynt.net`
  - Staging: `https://services-ote.poynt.net`
- `GatewayAPI.php` - Gateway-specific API implementation

**Request/Response Pattern:**
- All requests extend `AbstractRequest` (or `AbstractBusinessRequest` for business-scoped operations)
- All responses extend `AbstractResponse`
- Organized by domain:
  - `Cards/` - Tokenization and card operations
  - `Transactions/` - Capture, void, refund operations
  - `Poynt/` - Business, stores, orders, webhooks

### Synchronization System (`src/Sync/`)

Bidirectional sync between WooCommerce and Poynt terminals:

- **PoyntOrderSynchronizer** - Pushes WooCommerce orders to connected terminals
- **Jobs/PushOrdersJob** - Background job for order pushing
- **Jobs/PoyntTransactionSynchronizer** - Syncs terminal transactions to WooCommerce
- **Jobs/ActiveSmartTerminalDetector** - Detects connected terminals

### Webhooks (`src/Webhooks/`)

Handles incoming webhooks from Poynt API:

- **PoyntWebhooksHandler** - Main webhook router (listens on `woocommerce_api_poynt`)
- **PoyntOrderWebhookHandler** - Handles order-related events
- **PoyntTransactionWebhookHandler** - Handles transaction events
- Event resources: `/orders` and `/transactions`

### Helpers (`src/Helpers/`)

Domain-specific utility classes:
- `ArrayHelper` - Array manipulation utilities
- `MoneyHelper` - Currency and amount conversions
- `PoyntHelper` - Poynt API-specific helpers
- `WCHelper` - WooCommerce integration utilities
- `CredentialsHelper` - API credential management
- `StringHelper` - String operations

### Frontend Components

- **Frontend/Admin/Notices.php** - Admin notices and warnings
- **Frontend/MyAccount/PaymentMethods.php** - Customer payment method management
- **Blocks/** - WooCommerce Blocks integration for checkout
- **Pages/ViewOrderPage.php** - Order viewing customizations

### External Frontend Assets

The plugin bundles a React frontend package from `@gdcorp-partners/woocommerce-gateway-godaddy-payments`. When this package is updated:
1. Update the version in `package.json`
2. Run `npm install`
3. Run `npx sake bundle` to copy assets into `assets/js/blocks/` and `assets/css/blocks/`

These bundled assets are versioned and ship with each plugin release.

### Shipping Integration (`src/Shipping/`)

- **LocalPickup/LocalPickup.php** - Local pickup integration with terminal workflow
- **LocalDelivery/LocalDelivery.php** - Local delivery support
- **CoreShippingMethods.php** - WooCommerce core shipping method integration

### REST API (`src/REST/`)

Custom REST endpoints under `/wp-json/` for support and internal operations:
- Base: `AbstractController`
- `SupportController` - Support-related endpoints

## Testing

- **Unit tests**: Located in `tests/Unit/`, mirror the `src/` structure
- Uses Codeception and PHPUnit
- Tests require `@covers` annotations (enforced by `forceCoversAnnotation`)
- Mock objects in `tests/Mocks/`
- Run via: `vendor/bin/codecept run unit` or `composer test`

## Version Management

Version must be updated in 4 places before deployment:
1. `readme.txt` - Stable tag (line 1)
2. `readme.txt` - Changelog header
3. `godaddy-payments.php` - PHPDoc `@version` tag
4. `src/Plugin.php` - `VERSION` constant

Changelog format: `<YYYY>.nn.nn` (year + sequential numbers)

## Important Notes

- **Pre-commit hooks**: PHP CS Fixer runs automatically via `pre-commit install`
- **i18n/GoLF Integration**: Translations are managed by GoDaddy's internal team, except `en_CA` locale which only contains pricing info and must be manually compiled with PoEdit if updated
- **HPOS Support**: Plugin supports WooCommerce High-Performance Order Storage
- **Blocks Support**: Compatible with WooCommerce cart and checkout blocks
- **JWT Authentication**: Uses `firebase/php-jwt` for API authentication
