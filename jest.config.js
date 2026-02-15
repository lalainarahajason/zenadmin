module.exports = {
    testEnvironment: 'jsdom',
    roots: ['<rootDir>/tests/js'],
    testMatch: ['**/*.test.js'],
    transform: {
        '^.+\\.js$': 'babel-jest',
    },
    cacheDirectory: '<rootDir>/.jest-cache',
};
