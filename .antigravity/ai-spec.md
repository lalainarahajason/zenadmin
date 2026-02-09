# Specification: ZenAdmin (WP Plugin) — Enhanced Edition

## 1. Project Identity & Compliance

* **Plugin Name:** ZenAdmin
* **Namespace:** `ZenAdmin`
* **Global Prefix:** `zenadmin_` (Required for all functions, variables, and hooks)
* **License:** GPLv2 or later (WordPress standard)
* **Text Domain:** `zenadmin`
* **Minimum PHP Version:** 8.1
* **Version Schema:** Semantic Versioning (MAJOR.MINOR.PATCH)
* **Current Version:** 1.0.0

## 2. Functional Requirements

* **FR1 (Admin Bar Control):** Dropdown menu "ZenAdmin" containing:
  * **Toggle Zen Mode:** Activate/Deactivate selection mode.
  * **Settings:** Quick link to plugin configuration.
  * **Safe Mode:** Toggle to Activate/Exit Safe Mode.
  * **Troubleshooting:**
    * "Clear Session Blocks" (Immediate JS clear).
    * "Reset All Settings" (Full wipe of DB + Session with confirmation).
* **FR2 (Interaction):** 
  * **Hover:** Element gets a purple border (`#6e58ff`) and a temporary overlay to prevent interaction with the element itself.
  * **Click:** Custom WordPress-style modal (not native `confirm()`) with:
    * Preview of the generated selector
    * Editable label field (pre-filled with element text or tag name)
    * Option: "Block permanently" or "Hide for this session only"
    * "Cancel" and "Confirm" buttons

* **FR3 (Identification):** Generate stable CSS selectors. (See AS1).
* **FR4 (Persistence):** 
  * Store blocked selectors in `wp_options` under `zenadmin_blacklist`.
  * Store session-only blocks in JavaScript `sessionStorage`.
  * **Global Reset:** Ability to wipe both `zenadmin_blacklist` (DB) and `sessionStorage` (Client) simultaneously via Admin Bar or Settings.
* **FR5 (Execution):** Inject `<style>` in `admin_head` with optimized grouped selectors using `display: none !important;` for all stored selectors.
* **FR6 (Templates):** Provide pre-configured blocking templates for common annoyances (Yoast ads, Elementor upsells, generic plugin nags).

## 3. Strict Compliance & Security (The "Guardrails")

* **Naming:** No global symbols without `zenadmin_`.
* **Security Checks:** 
  * `check_admin_referer()` / `check_ajax_referer()` on every write.
  * `current_user_can( 'manage_options' )` on every admin action.

* **Data Sanitization:** 
  * Input: `sanitize_text_field()` or `absint()` for all `$_POST`/`$_GET`.
  * Output: `esc_html__()`, `esc_attr()`, or `wp_kses_post()` for ALL display.

* **Code Style:** Yoda Conditions, strict comparisons, and full DocBlocks are mandatory.

## 4. Technical Architecture

* **Performance:** 
  * Minimal PHP footprint. CSS injection is the priority.
  * Group CSS selectors when injecting (max efficiency).
  * Don't register `admin_head` hook if blacklist is empty.
* **File Structure:**
  * `zenadmin.php` (Entry point)
  * `includes/class-core.php` (Logic, Hooks, Injection)
  * `includes/class-settings.php` (Admin List Table)
  * `includes/class-templates.php` (Pre-configured blocking templates)
  * `assets/zen-engine.js` (Vanilla JS Selector Engine)
  * `assets/zen-modal.js` (Custom modal interface)
  * `assets/zen-styles.css` (Minimal modal styling)
  * `uninstall.php` (Clean-up)
  * `CHANGELOG.md` (Version history)

## 5. Advanced Logic & Safety (Enhanced)

### AS1: Intelligent Selector Heuristics

*   **Stability First:** The engine must crawl the DOM tree upwards to find the most reliable selector.
*   **Priority:** 
    1.  Static `ID` (if it doesn't look auto-generated like `id="notice-123"`).
    2.  Specific Classes (e.g., `.yoast-notice`, `.elementor-ad`).
    3.  Attribute Selectors (e.g., `[data-plugin-id]`).
    4.  Combined selectors for context (e.g., `#wpbody .custom-notice`).
*   **Restriction:** Never use generic WP classes (`.notice`, `.error`, `.updated`) alone. They must be combined with a unique parent or specific sibling class.
*   **Validation:** Before accepting a selector, check its **specificity score**:
    *   If it matches more than 10 elements on the page → Flag as "Too broad" and ask user to refine.
    *   If it contains only tag names (e.g., `div > span`) → Reject and suggest adding classes/IDs.

### AS1.1: Precise Targeting Strategy (Updated)

*   **HREF Strategy (Primary for Links):**
    *   If an element is a link (`<a>`) or inside one, prioritize the `href` attribute.
    *   Selector: `a[href="admin.php?page=zenadmin"]`.
    *   Rationale: Allows surgical blocking of specific submenu items without affecting siblings.
*   **ID Strategy:**
    *   Use `ID` if available and authentic (no digits like `id="el-1234"`).
    *   **Critical:** Always use `CSS.escape()` on IDs to handle special characters (e.g. `id="my:id"`).
*   **Structural Fallback (nth-of-type):**
    *   If no ID or unique Class/HREF is found, use the tag path with `:nth-of-type`.
    *   Example: `div#wpbody-content > div.wrap > ul > li:nth-of-type(3)`.
    *   Rationale: Ensures we target the *exact* visual element user clicked, even if it lacks unique identifiers.
*   **Class Safety:**
    *   Filter out generic classes (`zenadmin-*`, `ng-*`).
    *   **Critical:** Always use `CSS.escape()` on class names to handle Tailwind/framework special chars (e.g. `hover:bg-red`).

### AS2: Emergency Access (Safe Mode) — Enhanced

* **Requirement:** If `$_GET['zenadmin_safe_mode'] === '1'`, the plugin MUST NOT inject any blocking CSS **AND** must disable all JS session blocks.
* **Access:** 
  * **URL:** `?zenadmin_safe_mode=1`
  * **UI:** Admin Bar > ZenAdmin > Safe Mode
* **Smart Whitelist Logic:**
  * **New Logic:** `blockedElement.matches(whitelistSelector)` (Correct: Only prevents blocking the *container itself*).
* **Hardcoded Whitelist (Extended):** Prohibit blocking of:
  * `#wpadminbar`
  * `#wp-admin-bar-my-account`
  * `#adminmenu`
  * `#adminmenumain`
  * `.toplevel_page_zenadmin` (Settings page)
  * `#wpfooter`
  * `#wpbody-content > .wrap`
  * `.wrap > h1` (Page titles)
  * `#wpbody`
  * `.zenadmin-modal` (Own modal)
* **Warning System:** If user attempts to block a whitelisted element, show modal: "⚠️ This element is critical for WordPress Admin. Blocking it may break your dashboard."

### AS3: Data Contract (AJAX)

* **Action:** `zenadmin_save_block`
* **Payload:** 
```json
{
  "selector": "string",
  "label": "string",
  "session_only": "boolean",
  "security": "nonce"
}
```
* **Response:** 
```json
{
  "success": true/false,
  "data": {
    "id": "hash (sha256 of selector + timestamp)",
    "selector": "sanitized selector",
    "specificity_warning": "boolean"
  }
}
```

### AS4: Lifecycle & Cleanup

* **Uninstall:** `uninstall.php` must delete the `zenadmin_blacklist` option.
* **Optimization:** If the blacklist is empty, the `admin_head` hook for CSS injection should not even be registered.
* **Migration System:** Store a `zenadmin_schema_version` option. If plugin updates change data structure, run migration functions automatically.

### AS5: Conflict Resolution

* **Unique Hashing:** Each blocked element gets a unique ID: `hash('sha256', $selector . time() . wp_get_current_user()->ID)`.
* **Duplicate Detection:** Before saving, check if selector already exists. If yes, ask: "This element is already blocked. Update label or cancel?"
* **Cascade Warning:** Detect parent-child relationships. If user blocks `.wrap` and then `.wrap .notice`, warn: "You've already blocked the parent container."

### AS6: Debug Mode

* **Activation:** Set `define('ZENADMIN_DEBUG', true);` in `wp-config.php`.
* **Logging:** Write to `wp-content/debug.log`:
  * Timestamp + blocked selector + user ID
  * Rejected selectors (too generic)
  * Whitelist violation attempts
* **Admin Notice:** Show debug info in a dismissible notice: "ZenAdmin Debug: 3 elements blocked, 1 selector rejected (too broad)."

## 6. UI/UX Standards & Accessibility

* **Aesthetics:** 100% Native WP Admin Style. No external fonts or CSS frameworks.
* **Custom Modal (Accessible Design):**
  * **Markup:** Must use `role="dialog"`, `aria-modal="true"`, and `aria-labelledby` for screen readers.
  * **Focus Management (Focus Trap):** * When opened: Save `document.activeElement` and move focus to the first input.
    * Interaction: Tab and Shift+Tab must be trapped within modal elements.
    * When closed: Restore focus to the previously saved element.
  * **Dismissal:** Must close on `Escape` key press or click outside the modal content (overlay).
* **Dashicons:** * `dashicons-visibility` (Admin Bar toggle)
  * `dashicons-trash` (Delete action)
  * `dashicons-warning` (Specificity/Safety warnings)
* **Feedback:** * Use native `.notice.is-dismissible` for post-action confirmations.
  * Interactive elements must have `:focus` and `:hover` states consistent with WP Core.

## 7. Blocking Templates (New Feature)

### Template System
Pre-configured selector sets for common annoyances. Accessible via Settings page "Templates" tab.

**Template 1: Yoast SEO Upsells**
```php
[
  '.yoast-notification',
  '.yoast_premium_upsell',
  '[class*="yoast-upgrade"]'
]
```

**Template 2: Elementor Promotions**
```php
[
  '.elementor-templates-modal__promotion',
  '[class*="e-pro-banner"]',
  '.elementor-control-type-upgrade-promotion'
]
```

**Template 3: Generic Plugin Nags**
```php
[
  '.notice.is-dismissible[class*="review"]',
  '.notice.is-dismissible[class*="upgrade"]'
]
```

**Implementation:**
* Each template has: `name`, `description`, `selectors[]`, `icon`.
* One-click activation: "Apply Template" button.
* Templates stored in `includes/templates-config.php` as a PHP array.

## 8. Performance Optimization

### CSS Injection Strategy
Instead of:
```css
.selector1 { display: none !important; }
.selector2 { display: none !important; }
```

Output:
```css
.selector1, .selector2, .selector3 { display: none !important; }
```

**Implementation:**
```php
$selectors = get_option( 'zenadmin_blacklist', [] );
if ( ! empty( $selectors ) ) {
    $combined = implode( ', ', array_column( $selectors, 'selector' ) );
    echo '<style id="zenadmin-blocks">' . esc_html( $combined ) . ' { display: none !important; }</style>';
}
```

## 9. User Documentation

### In-Plugin Help
* **Settings page sidebar:** "How to use" widget with:
  * GIF demo of selection mode
  * Link to full documentation
  * Troubleshooting tips (use Safe Mode if locked out)

### External Resources
* `README.md` in plugin root with:
  * Installation instructions
  * FAQ (What happens if I break my admin? → Safe Mode)
  * Screenshots
  * Contribution guidelines

## 10. Testing Requirements

### Unit Tests (PHPUnit)
* `test_selector_sanitization()` — Ensure malicious input is cleaned
* `test_whitelist_protection()` — Verify critical elements can't be blocked
* `test_empty_blacklist_optimization()` — Confirm no CSS injection when list is empty

### Integration Tests
* Test AJAX save/delete flow
* Test Safe Mode activation
* Test template application

### Browser Tests
* Verify selector generation across different admin pages
* Test modal responsiveness
* Ensure no JavaScript errors in console

## 11. Migration & Versioning

### Schema Versioning
```php
// On plugin activation
add_option( 'zenadmin_schema_version', '1.0.0' );

// On update
if ( version_compare( get_option( 'zenadmin_schema_version' ), '2.0.0', '<' ) ) {
    zenadmin_migrate_to_v2();
}
```

### Changelog Format (CHANGELOG.md)
```markdown
## [1.0.0] - 2024-02-08
### Added
- Initial release
- Selection mode with hover preview
- Custom modal for blocking
- Safe Mode emergency access
- Blocking templates
```

## 12. Security Hardening (Additional)

* **Rate Limiting:** Max 50 blocks per user to prevent database bloat.
* **Capability Check on Uninstall:** Only allow uninstall if `current_user_can('manage_options')`.
* **XSS Prevention:** Never use `innerHTML` in JavaScript. Always use `textContent` or `createTextNode()`.
* **SQL Injection:** Not applicable (using `wp_options` only), but always use `$wpdb->prepare()` if custom queries are added later.

## 🚀 Implementation Roadmap

### Phase 1 (MVP)
- [ ] Core blocking functionality (FR1-FR5)
- [ ] Basic settings page
- [ ] Safe Mode
- [ ] Selector heuristics (AS1)

### Phase 2 (Enhanced)
- [ ] Custom modal interface
- [ ] Blocking templates
- [ ] Edit selector functionality
- [ ] Export/Import

### Phase 3 (Polish)
- [ ] Debug mode
- [ ] Unit tests
- [ ] In-plugin documentation
- [ ] Performance benchmarking

## 13. Git & Deployment Workflow

* **Atomic Commits:** Every push must represent a single logical change (e.g., "Add FR1 selector logic").
* **Commit Message Standard:** Use Conventional Commits (e.g., `feat:`, `fix:`, `docs:`, `refactor:`).
* **Pre-Push Checklist:** Before any push, the AI must:
  1. Check for `TODO` or `FIXME` left in code.
  2. Ensure no API keys or secrets are exposed.
  3. Validate PHP syntax (`php -l`).
* **Branching:** Never push directly to `main` (optional, depends on your workflow). Use feature branches named `feature/zenadmin-[task]`.