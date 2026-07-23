import type { EditorOptions, IStandaloneCodeEditor } from '@/types';
import { classNames } from '@/utils';
import MonacoEditor, { type BeforeMount, type Monaco, type OnMount } from '@monaco-editor/react';
import { __ } from '@wordpress/i18n';
import React, { useEffect, useMemo, useRef, useState } from 'react';
import { getLanguage, registerLanguages } from './languages';
import { validateCode, type ValidationError } from './validators';

interface EditorProps {
    /** The content to display in the editor */
    value: string;
    /** Optional filename to infer language from extension */
    filename?: string;
    /** Explicit Monaco language id. Takes precedence over `filename` detection. */
    language?: string;
    /** Whether the editor is read-only */
    readOnly?: boolean;
    /** Height of the editor */
    height?: string;
    /** Whether the editor is currently loading */
    loading?: boolean;
    /** Custom CSS class name */
    className?: string;
    /** Callback when content changes with optional error state */
    onChange?: (value: string | undefined, error: ValidationError | null) => void;
    /** Additional Monaco editor options */
    options?: EditorOptions;
    /** Show loading spinner */
    showSpinner?: boolean;
    /** Loading text */
    loadingText?: string;
}

const Editor: React.FC<EditorProps> = ({
    value,
    filename,
    language: languageProp,
    readOnly = false,
    height = '80vh',
    loading = false,
    className,
    onChange,
    options = {},
    showSpinner = true,
    loadingText
}) => {
    const [isInternalLoading, setIsInternalLoading] = useState(true);
    const editorRef = useRef<IStandaloneCodeEditor | null>(null);
    const monacoRef = useRef<Monaco | null>(null);
    const isLoading = (loading || isInternalLoading) && showSpinner;

    const defaultOptions: EditorOptions = useMemo(() => {
        return {
            tabSize: 4,
            insertSpaces: true,
            automaticLayout: true,
            minimap: { enabled: false },
            scrollBeyondLastLine: false,
            lineNumbers: 'on',
            fontSize: 12,
            folding: true,
            fontFamily: 'monospace',
            fontWeight: 'normal',
            fontLigatures: true,
            readOnly,
            ...options
        };
    }, [options, readOnly]);

    const language = useMemo(() => {
        if (languageProp) {
            return languageProp;
        }
        if (filename) {
            return getLanguage(filename);
        }
        return 'plaintext';
    }, [languageProp, filename]);

    // Register custom languages before Monaco creates the model, so the model
    // resolves to the right language id instead of falling back to plaintext.
    const handleBeforeMount: BeforeMount = (monaco) => {
        registerLanguages(language, monaco);
    };

    const handleEditorDidMount: OnMount = (editor, monaco) => {
        editorRef.current = editor;
        monacoRef.current = monaco;
        editor.setValue(value);
        setIsInternalLoading(false);
        // Register language and theme if needed
        registerLanguages(language, monaco);
        if (language === 'log') {
            monaco.editor.setTheme('logTheme');
        } else {
            monaco.editor.setTheme('vs');
        }
        const model = editor.getModel();
        // Attach listener for content changes
        model?.onDidChangeContent(() => {
            // Scroll to bottom when new content is added
            const lineCount = model.getLineCount();
            editor.revealLine(lineCount);
        });
    };

    const handleOnChange = (code: string | undefined) => {
        if (!code || !editorRef.current || !monacoRef.current) return;

        // Validate and get error state
        const error = validateCode(code, language, editorRef.current, monacoRef.current);
        onChange?.(code, error);
    };

    useEffect(() => {
        // Register language and theme on language change
        if (monacoRef.current) {
            registerLanguages(language, monacoRef.current);
            if (language === 'log') {
                monacoRef.current.editor.setTheme('logTheme');
            } else {
                monacoRef.current.editor.setTheme('vs');
            }
        }
    }, [language]);

    return (
        <div className={classNames('relative', className)}>
            {isLoading && (
                <div className="absolute inset-0 z-10 flex items-center justify-center bg-white/80 dark:bg-gray-900/80">
                    <span className="border-primary-500 inline-block h-8 w-8 animate-spin rounded-full border-4 border-t-transparent"></span>
                    <span className="text-primary-700 dark:text-primary-300 ml-3 text-sm font-medium">
                        {loadingText || __('Loading editor…', 'debug-suite')}
                    </span>
                </div>
            )}
            <MonacoEditor
                value={value}
                height={height}
                language={language}
                onChange={handleOnChange}
                beforeMount={handleBeforeMount}
                onMount={handleEditorDidMount}
                options={defaultOptions}
            />
        </div>
    );
};

export default Editor;
