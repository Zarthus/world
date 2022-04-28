module.exports = {
    root: true,
    env: {
        browser: true,
        node: true,
        es2021: true,
    },
    extends: [
        'airbnb-base',
    ],
    parserOptions: {
        ecmaVersion: 'latest',
        sourceType: 'module',
    },
    rules: {
        indent: ['error', 4],
        'sort-imports': 'error',
        camelcase: 'off',
        'func-names': 'off',
        'no-console': 'off',
    },
};
