# Specification: ZenAdmin (WP Plugin)

## 1. Project Identity & Compliance

* **Plugin Name:** ZenAdmin
* **Namespace:** `ZenAdmin`
* **Global Prefix:** `zenadmin_` (Required for all functions, variables, and hooks)
* **License:** GPLv2 or later (WordPress standard)
* **Text Domain:** `zenadmin`
* **Minimum PHP Version:** 8.1

## 2. Functional Requirements

* **FR1 (Target Mode):** Toggle in WP Admin Bar to activate/deactivate "Selection Mode".
* **FR2 (Interaction):** - Hover: Purple border (`#6e58ff`).
* Click: Confirmation modal using native WP UI or clean Vanilla JS.


* **FR3 (Identification):** Generate unique CSS selectors. Prioritize `id` over specific classes. Never use generic classes like `.notice`.
* **FR4 (Persistence):** Store blocked selectors in `wp_options` under `zenadmin_blacklist`.
* **FR5 (Execution):** Inject `<style>` in `admin_head` with `display: none !important;`.

## 3. Strict Compliance & Security (The "Guardrails")

* **Naming:** No global functions without `zenadmin_` prefix. No generic variables.
* **Security:**
* `check_admin_referer()` or `check_ajax_referer()` on all write actions.
* `current_user_can( 'manage_options' )` on all admin-related logic.


* **Sanitization/Escaping:**
* Input: `sanitize_text_field()` or `absint()` for all `$_POST`/`$_GET` data.
* Output: `esc_html__()`, `esc_attr()`, or `wp_kses_post()` for ALL display logic.

* **Code Style (WPCS):**
* Enforce **Yoda Conditions** (`if ( true === $var )`).
* Strict comparisons (`in_array( $n, $h, true )`).
* Full docblocks for every File, Class, and Function.


* **i18n:** All strings must be translatable using the `zenadmin` text domain.

## 4. Technical Architecture

* **No External Links:** No "Powered by" or tracking without explicit opt-in.
* **Performance:** TTFB impact must be near zero. CSS injection is the primary method.
* **File Structure:**
* `zenadmin.php` (Entry point)
* `includes/class-core.php` (Hooks, Logic)
* `includes/class-settings.php` (Admin Management)
* `assets/zen-engine.js` (Vanilla JS, No jQuery)

### SC1: Strict Data Handling
- **No Nonce, No Action:** Every single `$_POST`, `$_GET`, or `$_REQUEST` processing MUST start with a nonce verification.
- **Functions to use:** - For AJAX: `check_ajax_referer( 'zenadmin_ajax_action', 'security' );`
    - For Form POST: `check_admin_referer( 'zenadmin_save_settings', 'zenadmin_nonce' );`
- **Error Handling:** If nonce fails, the script must immediately `wp_die()` or return a `403` status.