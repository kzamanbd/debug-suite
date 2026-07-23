import type { Monaco } from '@monaco-editor/react';

// Log language registration function
export const registerLogLanguage = (monaco: Monaco) => {
    monaco.languages.register({ id: 'log' });

    monaco.languages.setMonarchTokensProvider('log', {
        tokenizer: {
            root: [
                [/\b(INFO|ERROR|WARN|DEBUG|NOTICE|CRITICAL)\b/, 'log-level'],
                [/\b(E_USER_DEPRECATED|E_WARNING|E_DEPRECATED)\b/, 'deprecated'],
                [/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/, 'timestamp'],
                [/\b(Exception|Error|Trace|Stack|Warning|Notice)\b/, 'exception'],
                [/\bGET|POST|PUT|DELETE|PATCH|HEAD|OPTIONS\b/, 'http'],
                [/https?:\/\/[^\s]+/, 'url'],
                [/".*?"/, 'string'],
                [/PHP \w+:/, 'php-error'],
                [/line \d+/, 'line-number'],
                [/\b\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\b/, 'ip-address'],
                [/\b\w+\.php\b/, 'filename']
            ]
        }
    });

    monaco.editor.defineTheme('logTheme', {
        base: 'vs',
        inherit: true,
        rules: [
            { token: 'log-level', foreground: '0000ff', fontStyle: 'bold' }, // Blue
            { token: 'timestamp', foreground: '666666' }, // Dark gray
            { token: 'exception', foreground: 'ff0000', fontStyle: 'bold' }, // Red
            { token: 'deprecated', foreground: 'ff8000', fontStyle: 'italic' }, // Orange
            { token: 'warning', foreground: 'ffa500', fontStyle: 'italic' }, // Orange
            { token: 'http', foreground: '008080', fontStyle: 'bold' }, // Teal
            { token: 'url', foreground: '0066cc', fontStyle: 'underline' }, // Blue
            { token: 'string', foreground: '008000' }, // Green
            { token: 'php-error', foreground: 'cc0000', fontStyle: 'bold' }, // Dark red
            { token: 'line-number', foreground: '666666', fontStyle: 'italic' }, // Gray
            { token: 'ip-address', foreground: '993399' }, // Purple
            { token: 'filename', foreground: '800000', fontStyle: 'bold' } // Maroon
        ],
        colors: {
            'editor.background': '#ffffff',
            'editor.foreground': '#000000',
            'editor.lineHighlightBackground': '#f0f0f0',
            'editorLineNumber.foreground': '#999999',
            'editor.selectionBackground': '#b3d4fc'
        }
    });
};

/**
 * Language id for bare PHP typed into the Query Console.
 *
 * Monaco's stock `php` grammar starts in HTML mode and only switches to PHP
 * when it sees a `<?php` tag — so a console buffer of bare PHP gets no
 * tokens at all. This id gets Monaco's real PHP grammar with its tokenizer
 * entry point moved straight to the PHP state.
 */
export const PHP_CONSOLE_LANGUAGE = 'php-console';

// PHP console language registration function
export const registerPhpConsoleLanguage = (monaco: Monaco) => {
    if (monaco.languages.getLanguages().some((lang) => lang.id === PHP_CONSOLE_LANGUAGE)) {
        return;
    }

    // The stock grammar is lazily loaded; grab its loader before registering ours.
    const php = monaco.languages
        .getLanguages()
        .find((lang) => lang.id === 'php') as { loader?: () => Promise<{ conf: unknown; language: unknown }> } | undefined;

    if (typeof php?.loader !== 'function') return;

    // Register the id synchronously so models created with it resolve correctly;
    // the grammar is attached as soon as it resolves and Monaco re-tokenizes.
    monaco.languages.register({ id: PHP_CONSOLE_LANGUAGE });

    void php.loader().then(({ conf, language }) => {
        const grammar = language as { tokenizer: Record<string, unknown> };

        monaco.languages.setLanguageConfiguration(
            PHP_CONSOLE_LANGUAGE,
            conf as Parameters<typeof monaco.languages.setLanguageConfiguration>[1]
        );

        monaco.languages.setMonarchTokensProvider(PHP_CONSOLE_LANGUAGE, {
            ...(grammar as object),
            tokenizer: {
                ...grammar.tokenizer,
                // Enter the PHP state immediately instead of waiting for `<?php`.
                root: [[/(?=.)/, { token: '@rematch', switchTo: '@phpRoot' }]]
            }
        } as Parameters<typeof monaco.languages.setMonarchTokensProvider>[1]);
    });
};

// Simple object lookup - no switch needed!
const languageRegistrations = {
    log: registerLogLanguage,
    [PHP_CONSOLE_LANGUAGE]: registerPhpConsoleLanguage
    // Add new language registrations here: python: registerPythonLanguage,
};

// Main language registration function that handles Monaco language setup
export const registerLanguages = (language: string, monaco: Monaco) => {
    // Get language registration function from object
    const registerLanguage = languageRegistrations[language as keyof typeof languageRegistrations];
    if (typeof registerLanguage !== 'function') return;

    registerLanguage(monaco);
};

// Mapping of file extensions to Monaco languages
const extensionToLanguageMap: Record<string, string> = {
    '.js': 'javascript',
    '.ts': 'typescript',
    '.jsx': 'javascript',
    '.tsx': 'typescript',
    '.py': 'python',
    '.cpp': 'cpp',
    '.html': 'html',
    '.css': 'css',
    '.json': 'json',
    '.xml': 'xml',
    '.yml': 'yaml',
    '.yaml': 'yaml',
    '.md': 'markdown',
    '.sh': 'shell',
    '.sql': 'sql',
    '.java': 'java',
    '.php': 'php',
    '.rb': 'ruby',
    '.go': 'go',
    '.cs': 'csharp',
    '.swift': 'swift',
    '.lock': 'json',
    '.log': 'log'
};

export function getLanguage(filename: string, explicitLanguage?: string): string {
    if (explicitLanguage) {
        return explicitLanguage;
    }

    const lastDot = filename.lastIndexOf('.');
    const ext = lastDot !== -1 ? filename.slice(lastDot) : '';
    return extensionToLanguageMap[ext] || 'plaintext';
}
