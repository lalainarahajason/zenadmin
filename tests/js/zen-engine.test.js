/**
 * @jest-environment jsdom
 */

// Mock jQuery to avoid npm install errors locally
const mockJQuery = function (selector) {
    return {
        ready: function (callback) {
            callback();
        }
    };
};
global.$ = global.jQuery = mockJQuery;

describe('ZenAdmin Engine', () => {
    let Engine;

    beforeEach(() => {
        // Reset DOM
        document.body.innerHTML = `
            <div id="wp-admin-bar-zenadmin-toggle"></div>
            <div id="wp-admin-bar-zenadmin-clear-session"><a></a></div>
            <div class="zenadmin-hover-overlay"></div>
        `;
        document.body.classList.remove('zenadmin-mode-active');

        // Mock Config
        window.zenadminConfig = {
            nonce: 'test-nonce',
            ajaxUrl: '/wp-admin/admin-ajax.php',
            whitelist: ['#wpadminbar'],
            blocked: {},
            i18n: { confirmTitle: 'Hide', confirm: 'Hide', cancel: 'Cancel' }
        };

        // Load the file content to execute the IIFE
        jest.resetModules();
        require('../../assets/zen-engine.js');

        Engine = window.ZenAdminEngine;
    });

    test('Engine should be defined', () => {
        expect(Engine).toBeDefined();
    });

    test('toggleMode should toggle class on body', () => {
        // Initial state
        expect(Engine.isActive).toBe(false);
        expect(document.body.classList.contains('zenadmin-mode-active')).toBe(false);

        // Toggle ON
        Engine.toggleMode();
        expect(Engine.isActive).toBe(true);
        expect(document.body.classList.contains('zenadmin-mode-active')).toBe(true);

        // Toggle OFF
        Engine.toggleMode();
        expect(Engine.isActive).toBe(false);
        expect(document.body.classList.contains('zenadmin-mode-active')).toBe(false);
    });

    test('toggleMode(true) should force active state', () => {
        Engine.toggleMode(true);
        expect(Engine.isActive).toBe(true);
    });
});
