/**
 * ESLint Configuration for Debug Suite WordPress Plugin
 *
 * Modern ESLint configuration with TypeScript, React, and WordPress integration.
 * Follows Debug Suite coding standards and enterprise architecture patterns.
 *
 * @since 1.0.0
 */
import js from '@eslint/js';
import reactHooks from 'eslint-plugin-react-hooks';
import reactRefresh from 'eslint-plugin-react-refresh';
import globals from 'globals';
import tseslint from 'typescript-eslint';

export default tseslint.config([
    // TypeScript and React configuration - Primary frontend files
    {
        files: ['src/**/*.{ts,tsx}'],
        extends: [js.configs.recommended, tseslint.configs.recommended],
        languageOptions: {
            ecmaVersion: 'latest',
            sourceType: 'module',
            globals: {
                ...globals.browser,
                ...globals.es2021
            },
            parserOptions: {
                project: './tsconfig.json',
                tsconfigRootDir: import.meta.dirname,
                ecmaFeatures: {
                    jsx: true
                }
            }
        },
        plugins: {
            'react-hooks': reactHooks,
            'react-refresh': reactRefresh
        },
        settings: {
            react: {
                version: 'detect'
            }
        },
        rules: {
            'no-console': ['warn', { allow: ['warn', 'error'] }],
            'no-debugger': 'warn',
            // 'no-alert': 'warn', // TODO: enable when ready
            'prefer-const': 'error',
            'no-var': 'error'
        }
    }
]);
