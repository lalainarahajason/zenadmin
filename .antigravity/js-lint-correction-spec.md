# Specification: JS WordPress Standards Correction

## 1. Formatting (Prettier & WP)
- **Tabs only:** Replace all indentation spaces with real tabs.
- **Line Breaks:** Follow Prettier suggestions for multi-line function arguments and long strings.

## 2. Modern JS Syntax (ES6+)
- **Method Shorthand:** Convert all `key: function()` definitions to shorthand `key()`.
- **Block Scoping:** Ensure `if`, `else`, `for`, and `while` always use curly braces `{}` even for single lines.

## 3. Global Scope & Safety
- **Globals Declaration:** Add `/* global ZenAdminToast, zenadminConfig, jQuery, confirm, sessionStorage */` at the top of JS files.
- **Alerts:** Replace or wrap `confirm()` calls to satisfy `no-alert` rules, or add `// eslint-disable-line no-alert` if strictly necessary.

## 4. File Targets
- Apply specifically to `assets/zen-engine.js` and `assets/zen-modal.js`.