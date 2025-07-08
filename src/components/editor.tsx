/**
 * Generic Editor component.
 *
 * A flexible code editor component built with Monaco Editor.
 * Can be used standalone or within modals for various editing needs.
 *
 * @since 1.0.0
 */
import { classNames } from '@/utils';
import MonacoEditor from '@monaco-editor/react';
import { useEffect, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

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
    '.lock': 'json'
};

/**
 * Get Monaco language from file extension or explicit language.
 *
 * @since 1.0.0
 * @param filename File name or language identifier
 * @param explicitLanguage Optional explicit language override
 * @return Monaco language string
 */
function getLanguageFromExtension(filename: string, explicitLanguage?: string): string {
    if (explicitLanguage) {
        return explicitLanguage;
    }

    const lastDot = filename.lastIndexOf('.');
    const ext = lastDot !== -1 ? filename.slice(lastDot) : '';
    return extensionToLanguageMap[ext] || 'plaintext';
}

/**
 * Props for the Editor component.
 *
 * @since 1.0.0
 */
interface EditorProps {
    /** The content to display in the editor */
    content: string;
    /** Optional language for syntax highlighting */
    language?: string;
    /** Optional filename to infer language from extension */
    filename?: string;
    /** Whether the editor is read-only */
    readOnly?: boolean;
    /** Height of the editor */
    height?: string;
    /** Whether the editor is currently loading */
    loading?: boolean;
    /** Custom CSS class name */
    className?: string;
    /** Callback when content changes */
    onChange?: (value: string | undefined) => void;
    /** Callback when editor is ready */
    onMount?: (editor: any) => void;
    /** Additional Monaco editor options */
    options?: Record<string, any>;
    /** Show loading spinner */
    showLoadingSpinner?: boolean;
    /** Loading text */
    loadingText?: string;
}

/**
 * Generic Editor component.
 *
 * @since 1.0.0
 */
const Editor = ({
    content,
    language,
    filename,
    readOnly = false,
    height = '400px',
    loading = false,
    className,
    onChange,
    onMount,
    options = {},
    showLoadingSpinner = true,
    loadingText
}: EditorProps): JSX.Element => {
    const editorRef = useRef<any>(undefined);
    const [isInternalLoading, setIsInternalLoading] = useState(true);

    // Determine the language to use
    const editorLanguage = getLanguageFromExtension(filename || '', language);

    // Default Monaco options
    const defaultOptions = {
        readOnly,
        tabSize: 4,
        insertSpaces: true,
        automaticLayout: true,
        minimap: { enabled: false },
        scrollBeyondLastLine: false,
        lineNumbers: 'on' as const,
        folding: true,
        wordWrap: 'on' as const,
        fontSize: 14,
        ...options
    };

    const handleEditorDidMount = (editor: any) => {
        editorRef.current = editor;
        setIsInternalLoading(false);

        // Focus the editor if not read-only
        if (!readOnly) {
            editor.focus();
        }

        // Call external onMount callback if provided
        if (onMount) {
            onMount(editor);
        }
    };

    useEffect(() => {
        if (editorRef.current && content !== undefined) {
            const currentValue = editorRef.current.getValue();
            if (currentValue !== content) {
                editorRef.current.setValue(content);
            }
        }
    }, [content]);

    const isLoading = loading || isInternalLoading;
    const displayLoadingText = loadingText || __('Loading editor…', 'debug-suite');

    return (
        <div className={classNames('relative', className)}>
            {isLoading && showLoadingSpinner && (
                <div className="absolute inset-0 z-10 flex items-center justify-center bg-white/80 dark:bg-gray-900/80">
                    <span className="border-primary-500 inline-block h-8 w-8 animate-spin rounded-full border-4 border-t-transparent"></span>
                    <span className="text-primary-700 dark:text-primary-300 ml-3 text-sm font-medium">
                        {displayLoadingText}
                    </span>
                </div>
            )}
            <MonacoEditor
                height={height}
                defaultValue={content}
                language={editorLanguage}
                onMount={handleEditorDidMount}
                onChange={onChange}
                options={defaultOptions}
            />
        </div>
    );
};

export default Editor;
