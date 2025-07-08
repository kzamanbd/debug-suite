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
    // Global ignores - Files and directories to exclude from linting
    {
        ignores: [
            // Build outputs and generated files
            'dist/**',
            'build/**',
            'assets/js/**',
            'assets/css/**',
            '**/*.asset.php',
            '**/*.d.ts',

            // Dependencies and vendor files
            'node_modules/**',
            'vendor/**',
            'pnpm-lock.yaml',
            'package-lock.json',
            '*.min.js',
            '*.min.css',

            // WordPress core and PHP files (backend handled separately)
            'includes/**/*.php',
            'tests/**/*.php',
            'languages/**',
            '*.pot',
            '*.po',
            '*.mo',

            // Configuration files that don't need strict linting
            'webpack.config.js',
            'phpstan.neon',
            'phpcs.xml'
        ]
    },

    // TypeScript and React configuration - Primary frontend files
    {
        files: ['src/**/*.{ts,tsx}'],
        extends: [
            js.configs.recommended,
            tseslint.configs.recommended,
            tseslint.configs.recommendedTypeChecked,
            tseslint.configs.stylisticTypeChecked
        ],
        languageOptions: {
            ecmaVersion: 'latest',
            sourceType: 'module',
            globals: {
                ...globals.browser,
                ...globals.es2021,
                // WordPress globals - Essential for WordPress plugin development
                wp: 'readonly',
                wpApiSettings: 'readonly',
                ajaxurl: 'readonly',
                debugSuite: 'readonly',
                // WordPress REST API
                wpRestNonce: 'readonly',
                wpRestUrl: 'readonly',
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
            // ===== TYPESCRIPT RULES - Enhanced for Debug Suite Standards =====

            // Variable and import management
            '@typescript-eslint/no-unused-vars': [
                'error',
                {
                    argsIgnorePattern: '^_',
                    varsIgnorePattern: '^_',
                    caughtErrorsIgnorePattern: '^_',
                    destructuredArrayIgnorePattern: '^_'
                }
            ],
            '@typescript-eslint/consistent-type-imports': [
                'error',
                {
                    prefer: 'type-imports',
                    fixStyle: 'separate-type-imports',
                    disallowTypeAnnotations: false
                }
            ],
            '@typescript-eslint/consistent-type-exports': [
                'error',
                { fixMixedExportsWithInlineTypeSpecifier: false }
            ],

            // Type definitions and safety
            '@typescript-eslint/consistent-type-definitions': ['error', 'interface'],
            '@typescript-eslint/array-type': ['error', { default: 'array-simple' }],
            '@typescript-eslint/no-explicit-any': 'warn',
            '@typescript-eslint/prefer-nullish-coalescing': 'off',
            '@typescript-eslint/no-non-null-assertion': 'warn',
            '@typescript-eslint/no-unnecessary-condition': 'error',
            '@typescript-eslint/prefer-includes': 'error',
            '@typescript-eslint/prefer-string-starts-ends-with': 'error',

            // Function and method patterns
            '@typescript-eslint/explicit-function-return-type': 'off', // Allow inference for React components
            '@typescript-eslint/explicit-module-boundary-types': 'off',
            '@typescript-eslint/prefer-function-type': 'error',

            // WordPress-specific TypeScript patterns (relaxed for development)
            '@typescript-eslint/no-unsafe-assignment': 'warn',
            '@typescript-eslint/no-unsafe-call': 'warn',
            '@typescript-eslint/no-unsafe-member-access': 'warn',
            '@typescript-eslint/no-unsafe-return': 'warn',
            '@typescript-eslint/no-unsafe-argument': 'warn',
            '@typescript-eslint/restrict-template-expressions': 'warn',

            // ===== REACT HOOKS RULES =====
            ...reactHooks.configs.recommended.rules,
            'react-hooks/exhaustive-deps': 'warn',

            // ===== REACT REFRESH RULES =====
            'react-refresh/only-export-components': [
                'warn',
                { allowConstantExport: true }
            ],

            // ===== IMPORT AND MODULE ORGANIZATION =====
            'sort-imports': [
                'error',
                {
                    ignoreCase: true,
                    ignoreDeclarationSort: true, // Let TypeScript handle this
                    ignoreMemberSort: false,
                    memberSyntaxSortOrder: ['none', 'all', 'multiple', 'single'],
                    allowSeparatedGroups: true
                }
            ],

            // ===== CODE QUALITY AND PERFORMANCE =====
            'no-console': ['warn', { allow: ['warn', 'error'] }],
            'no-debugger': 'warn',
            // 'no-alert': 'warn', // TODO: enable when ready
            'prefer-const': 'error',
            'no-var': 'error',
            'object-shorthand': 'error',
            'prefer-arrow-callback': 'error',
            'prefer-template': 'error',
            'prefer-destructuring': [
                'error',
                {
                    array: true,
                    object: true
                },
                {
                    enforceForRenamedProperties: false
                }
            ],

            // ===== WORDPRESS AND ACCESSIBILITY PATTERNS =====
            'camelcase': 'off', // WordPress uses snake_case for PHP, camelCase for JS
            'no-underscore-dangle': 'off', // WordPress translation functions use underscores

            // ===== DEBUG SUITE SPECIFIC PATTERNS =====
            // Enforce consistent component prop patterns
            '@typescript-eslint/prefer-readonly-parameter-types': 'off', // Too strict for React props

            // Allow empty interfaces for extending HTML attributes
            '@typescript-eslint/no-empty-interface': [
                'error',
                {
                    allowSingleExtends: true
                }
            ],

            // Performance and React patterns
            '@typescript-eslint/no-misused-promises': [
                'error',
                {
                    checksVoidReturn: {
                        attributes: false // Allow async event handlers
                    }
                }
            ]
        }
    },

    // Configuration for JavaScript files (legacy support and build tools)
    {
        files: ['**/*.{js,jsx}'],
        excludes: ['src/**/*'], // Exclude src directory (TypeScript only)
        extends: [js.configs.recommended],
        languageOptions: {
            ecmaVersion: 'latest',
            sourceType: 'module',
            globals: {
                ...globals.browser,
                ...globals.node,
                wp: 'readonly',
                wpApiSettings: 'readonly',
                ajaxurl: 'readonly'
            }
        },
        rules: {
            'no-console': ['warn', { allow: ['warn', 'error'] }],
            'prefer-const': 'error',
            'no-var': 'error',
            'object-shorthand': 'error'
        }
    },

    // Configuration files (webpack, build tools, etc.)
    {
        files: [
            '*.config.{js,mjs,cjs}',
            'webpack.config.js',
            'eslint.config.js',
            'tailwind.config.js'
        ],
        languageOptions: {
            ecmaVersion: 'latest',
            sourceType: 'module',
            globals: {
                ...globals.node,
                process: 'readonly',
                __dirname: 'readonly',
                __filename: 'readonly'
            }
        },
        rules: {
            'no-console': 'off',
            '@typescript-eslint/no-require-imports': 'off',
            '@typescript-eslint/no-var-requires': 'off'
        }
    },

    // Test files configuration
    {
        files: ['**/*.test.{ts,tsx}', '**/*.spec.{ts,tsx}'],
        languageOptions: {
            globals: {
                ...globals.jest,
                describe: 'readonly',
                it: 'readonly',
                expect: 'readonly',
                beforeEach: 'readonly',
                afterEach: 'readonly'
            }
        },
        rules: {
            '@typescript-eslint/no-explicit-any': 'off', // Allow any in tests
            '@typescript-eslint/no-non-null-assertion': 'off' // Allow non-null assertions in tests
        }
    }
]); 