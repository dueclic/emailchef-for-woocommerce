# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

Official Emailchef for WooCommerce plugin (slug: `emailchef-for-woocommerce`). It connects a WooCommerce store to Emailchef: customers and orders are synced into an Emailchef list as contacts (with e-commerce custom fields), newsletter subscriptions are managed at checkout (single or double opt-in), and abandoned carts are tracked and pushed to Emailchef for recovery campaigns.

Requires WooCommerce 8.3.1+; HPOS (custom order tables) compatibility is declared.

## Commands

Toolchain: Node 20 (`.nvmrc`) + pnpm 9 (pinned via `packageManager`, use through corepack: `corepack enable` once, then plain `pnpm` works).

Admin CSS/JS are compiled from `src/` to `dist/` with gulp; **`dist/` is committed** — after editing `src/`, rebuild and commit the regenerated files too.

```bash
pnpm install
pnpm run build        # clean + styles + scripts (dist/css, dist/js)
pnpm run watch        # watch scss + js
pnpm run build:css    # gulp styles only
pnpm run build:js     # gulp scripts only
```

There are no tests and no PHP linting configured; PHP is loaded directly by WordPress.

## Local dev environment (wp-env)

`@wordpress/env` (needs Docker) spins up WordPress with WooCommerce (latest stable) and the plugin mounted and activated; config in `.wp-env.json` (PHP 8.2, `WP_DEBUG` on).

```bash
pnpm run env:start    # http://localhost:8888 — admin: admin/password
pnpm run env:stop
pnpm run env:destroy  # remove containers + volumes
pnpm run env:cli ...  # WP-CLI inside the container, e.g. pnpm run env:cli option get wc_emailchef_list
```

## Architecture

### Bootstrap (woocommerce-emailchef.php)

Defines `WC_EMAILCHEF_FILE` / `WC_EMAILCHEF_VERSION`, a set of global helpers (`wc_ec_*`: customer totals over a period, ordered product IDs, last order, option helpers — option names are prefixed through the `wc_ec_add_prefix` filter, resolving to `wc_emailchef_<name>`), and boots the `WC_Emailchef_Plugin` singleton via `WCEC()`.

### Core plugin (includes/class-wc-emailchef-plugin.php)

`WC_Emailchef_Plugin` (singleton) defines constants (`WC_EMAILCHEF_DIR`, `WC_EMAILCHEF_URL`, `WC_EMAILCHEF_SETTINGS_URL`), registers activation/deactivation hooks (deactivation deletes the Emailchef integration for the configured list and all `wc_emailchef_*` options), loads settings (`conf/default_settings.php` defaults merged with saved options; filters `wc_emailchef_default_settings` / `wc_emailchef_settings`), enqueues the `dist/` assets on the WooCommerce settings tab and the debug page, declares HPOS compatibility, and registers the `emailchef_15_minutes` cron schedule. `ecwc_plugin_update_check()` resets the plugin configuration when upgrading from a version older than 5.4.

### API client (includes/class-wc-emailchef-api.php, includes/class-wc-emailchef.php)

`WC_Emailchef_Api::call()` wraps `wp_remote_request()` against `https://app.emailchef.com/apps/api/v1` (base URL overridable with the `EMAILCHEF_API_URL` constant; request args filterable via `ec_wc_get_args`), authenticating with `consumerKey`/`consumerSecret` headers. `WC_Emailchef` extends it with one method per API operation (lists, `upsert_customer`, `sync_list`, `upsert_integration` / `delete_integration`, abandoned-cart sync, custom-field creation from `conf/custom_fields.php`, …).

### Event handler (includes/class-wc-emailchef-handler.php)

`WC_Emailchef_Handler` (singleton) wires the WooCommerce side: order status changes sync the customer (or guest, from order data) to the configured list; the checkout opt-in checkbox (labels/behavior driven by the `policy_type` setting) records consent; double opt-in sends a confirmation email (`emails/opt_in.php` / `opt_in_it.php` templates with `[[placeholders]]`) whose links hit the `emailchef/subscribe|unsubscribe` REST endpoints; abandoned carts are captured into the custom `{$wpdb->prefix}emailchef_abcart` table and pushed to Emailchef by the `emailchef_abandoned_cart_sync` cron. It also registers the admin AJAX endpoints (manual sync, lists, add list, disconnect, debug tools), all nonce-protected.

### Customer payload (includes/class-wc-emailchef-customer.php)

`WC_Emailchef_Customer` builds the contact payload sent to Emailchef (totals spent over several periods, order count, last order details, registration date, ordered product IDs, …) from user meta and orders.

### Settings & admin UI

`WC_Emailchef_Settings` adds the "Emailchef" tab (`id: emailchef`) to WooCommerce settings. A hidden debug page (`admin_page_emailchef-debug`, markup in `partials/admin-debug.php`) exposes maintenance actions (rebuild custom fields, move abandoned carts). All options are `wc_emailchef_`-prefixed: `consumer_key`, `consumer_secret`, `enabled`, `list`, `policy_type`, `landing_page`, `unsubscription_page`, `cron_end_interval_value`.

### CLI helper (cli/import.php)

Standalone script (loads `wp-load.php`, CLI-only) that re-creates the integration and force-syncs the configured list.

## Translations

All user-facing strings use the `emailchef-for-woocommerce` text domain; translations are delivered as language packs from translate.wordpress.org. The POT catalog lives in `languages/`.

## Deployment

Deployment to the WordPress.org SVN repo happens automatically via GitHub Actions (`.github/workflows/deploy.yml`) when a git tag is pushed; the same workflow attaches the deployed zip to a GitHub release for the tag. A package check workflow (`.github/workflows/build.yml`) runs on every PR and uploads the zip as an artifact. Pushes to `master` touching `src/` trigger `.github/workflows/fe-compile.yml`, which rebuilds `dist/` and commits it if it changed. `.distignore` controls what is excluded from the deployed zip (`src/`, build tooling and dev files are excluded; `dist/` ships).

## Conventions

- Code style is WordPress-flavored PHP as in existing files; match the surrounding file.
- Everything written to the repo or GitHub is in **English**: PR titles and bodies, commit messages, code comments, and docs — regardless of the language used in conversation.
