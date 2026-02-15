module.exports = {
    root: true,
    extends: [
        'plugin:@wordpress/eslint-plugin/recommended'
    ],
    env: {
        browser: true,
        es6: true,
        jquery: true,
        jest: true
    },
    globals: {
        wp: 'readonly',
        zenadmin: 'readonly'
    },
    rules: {
        // Customize rules here if needed
    }
};
